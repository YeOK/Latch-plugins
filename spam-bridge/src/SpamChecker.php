<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

use Latch\Core\Plugins\PostSaveContext;
use Latch\Core\Request;

/**
 * Orchestrates Akismet / Stop Forum Spam checks and spam_log writes.
 */
final class SpamChecker
{
    private const BLOCK_MESSAGE = 'Your submission was flagged as spam. Contact an administrator if you believe this is an error.';

    public function __construct(
        private readonly Settings $settings,
        private readonly PluginConfig $config,
        private readonly ?AkismetClient $akismet,
        private readonly StopForumSpamClient $sfs,
        private readonly SpamLog $log,
        private readonly Request $request,
        private readonly string $siteUrl,
        private readonly RegistrationEnforcer $registrationEnforcer,
    ) {
    }

    public function checkPost(PostSaveContext $ctx): ?string
    {
        if ($this->shouldBypassStaff($ctx->user)) {
            return null;
        }

        $userId = (int) ($ctx->user['id'] ?? 0);
        $username = (string) ($ctx->user['username'] ?? '');
        $email = (string) ($ctx->user['email'] ?? '');
        $content = $ctx->body;
        if ($ctx->topicTitle !== null && $ctx->topicTitle !== '') {
            $content = $ctx->topicTitle . "\n\n" . $content;
        }

        $permalink = $this->buildPermalink($ctx);
        $commentType = $ctx->kind === 'reply' ? 'reply' : 'forum-post';

        $akismetSpam = false;
        $akismetPayload = [];
        if ($this->settings->usesAkismet() && $this->akismet !== null) {
            $akismetPayload = $this->akismet->commentCheck([
                'blog' => $this->siteUrl,
                'user_ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'referrer' => $this->request->header('Referer'),
                'permalink' => $permalink,
                'comment_type' => $commentType,
                'comment_author' => $username,
                'comment_author_email' => $email,
                'comment_content' => $content,
                'comment_date_gmt' => gmdate('c'),
            ]);
            $akismetSpam = $akismetPayload['spam'];
        }

        $sfsPayload = [];
        $sfsMatches = [];
        $sfsFlagged = false;
        if ($this->settings->usesStopForumSpam()) {
            $sfsPayload = $this->sfs->check([
                'ip' => $this->request->ip(),
                'email' => $email,
                'username' => $username,
            ]);
            $sfsMatches = $sfsPayload['matches'] ?? [];
            $sfsFlagged = $this->sfsHasMatch($sfsMatches);
        }

        if (!$akismetSpam && !$sfsFlagged) {
            return null;
        }

        $provider = $this->resolveProviderLabel($akismetSpam, $sfsFlagged);
        $reason = $this->buildReason($akismetSpam, $sfsFlagged, $akismetPayload, $sfsPayload);

        $this->log->record(
            kind: 'post',
            provider: $provider,
            userId: $userId > 0 ? $userId : null,
            postId: isset($ctx->post['id']) ? (int) $ctx->post['id'] : null,
            reason: $reason,
            payload: [
                'akismet' => $akismetPayload,
                'stop_forum_spam' => $sfsPayload,
                'kind' => $ctx->kind,
            ],
        );

        if (!$this->shouldBlock($akismetSpam, $sfsMatches)) {
            return null;
        }

        return self::BLOCK_MESSAGE;
    }

    /**
     * @param array<string, mixed> $user
     */
    public function checkRegistration(array $user): void
    {
        if (!$this->settings->checkRegistrations) {
            return;
        }

        if ($this->shouldBypassStaff($user)) {
            return;
        }

        $userId = (int) ($user['id'] ?? 0);
        $username = (string) ($user['username'] ?? '');
        $email = (string) ($user['email'] ?? '');
        $akismetSpam = false;
        $akismetPayload = [];
        if ($this->settings->usesAkismet() && $this->akismet !== null) {
            $akismetPayload = $this->akismet->commentCheck([
                'blog' => $this->siteUrl,
                'user_ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'referrer' => $this->request->header('Referer'),
                'comment_type' => 'signup',
                'comment_author' => $username,
                'comment_author_email' => $email,
                'comment_content' => $username,
            ]);
            $akismetSpam = $akismetPayload['spam'];
        }

        $sfsPayload = [];
        $sfsMatches = [];
        $sfsFlagged = false;
        if ($this->settings->usesStopForumSpam()) {
            $sfsPayload = $this->sfs->check([
                'ip' => $this->request->ip(),
                'email' => $email,
                'username' => $username,
            ]);
            $sfsMatches = $sfsPayload['matches'] ?? [];
            $sfsFlagged = $this->sfsHasMatch($sfsMatches);
        }

        if (!$akismetSpam && !$sfsFlagged) {
            return;
        }

        $provider = $this->resolveProviderLabel($akismetSpam, $sfsFlagged);
        $reason = $this->buildReason($akismetSpam, $sfsFlagged, $akismetPayload, $sfsPayload);

        $this->log->record(
            kind: 'registration',
            provider: $provider,
            userId: $userId > 0 ? $userId : null,
            postId: null,
            reason: $reason,
            payload: [
                'akismet' => $akismetPayload,
                'stop_forum_spam' => $sfsPayload,
            ],
        );

        if ($userId > 0 && $this->shouldBlock($akismetSpam, $sfsMatches)) {
            $this->registrationEnforcer->banSpamRegistration($userId, $provider);
        }
    }

    /**
     * @param array<string, mixed> $user
     */
    private function shouldBypassStaff(array $user): bool
    {
        if (!$this->settings->staffBypass) {
            return false;
        }

        $role = (string) ($user['role'] ?? '');

        return in_array($role, ['admin', 'mod'], true);
    }

    private function buildPermalink(PostSaveContext $ctx): string
    {
        $base = rtrim($this->siteUrl, '/');
        $boardSlug = (string) ($ctx->board['slug'] ?? '');
        if ($boardSlug === '') {
            return $base . '/';
        }

        if ($ctx->topic !== null) {
            $topicId = (int) ($ctx->topic['id'] ?? 0);
            if ($topicId > 0) {
                return $base . '/board/' . rawurlencode($boardSlug) . '/topic/' . $topicId;
            }
        }

        return $base . '/board/' . rawurlencode($boardSlug);
    }

    /**
     * @param list<array{field: string, appears: bool, frequency: int, confidence: float}> $matches
     */
    private function sfsHasMatch(array $matches): bool
    {
        foreach ($matches as $match) {
            if ($match['appears'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{field: string, appears: bool, frequency: int, confidence: float}> $matches
     */
    private function sfsShouldBlock(array $matches): bool
    {
        foreach ($matches as $match) {
            if (!($match['appears'] ?? false)) {
                continue;
            }

            if ($this->settings->strictness === 1 && (int) ($match['frequency'] ?? 0) >= 255) {
                return true;
            }

            if ($this->settings->strictness === 2 && (float) ($match['confidence'] ?? 0.0) >= $this->settings->sfsMinConfidence) {
                return true;
            }

            if ($this->settings->strictness >= 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array{field: string, appears: bool, frequency: int, confidence: float}> $sfsMatches
     */
    private function shouldBlock(bool $akismetSpam, array $sfsMatches): bool
    {
        if ($this->settings->strictness === 0) {
            return false;
        }

        if ($akismetSpam && $this->settings->usesAkismet()) {
            return true;
        }

        if (!$this->settings->usesStopForumSpam()) {
            return false;
        }

        return $this->sfsShouldBlock($sfsMatches);
    }

    /**
     * @param array<string, mixed> $akismetPayload
     * @param array<string, mixed> $sfsPayload
     */
    private function buildReason(bool $akismetSpam, bool $sfsSpam, array $akismetPayload, array $sfsPayload): string
    {
        $parts = [];
        if ($akismetSpam) {
            $parts[] = 'akismet:spam';
        } elseif (($akismetPayload['error'] ?? null) !== null) {
            $parts[] = 'akismet:' . (string) $akismetPayload['error'];
        }

        if ($sfsSpam) {
            $parts[] = 'sfs:spam';
        } elseif (($sfsPayload['success'] ?? false) === false) {
            $parts[] = 'sfs:unavailable';
        }

        return $parts !== [] ? implode('; ', $parts) : 'spam';
    }

    private function resolveProviderLabel(bool $akismetSpam, bool $sfsSpam): string
    {
        if ($akismetSpam && $sfsSpam) {
            return 'both';
        }

        if ($akismetSpam) {
            return 'akismet';
        }

        return 'stop_forum_spam';
    }
}