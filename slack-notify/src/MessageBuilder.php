<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

use Latch\Core\Plugins\PostSaveContext;

final class MessageBuilder
{
    public function __construct(
        private readonly Settings $settings,
        private readonly string $siteUrl,
    ) {
    }

    /**
     * @return array{text: string, discord_content: string}
     */
    public function forPost(PostSaveContext $ctx): array
    {
        $author = (string) ($ctx->user['username'] ?? 'member');
        $boardSlug = (string) ($ctx->board['slug'] ?? '');
        $boardLabel = $boardSlug !== '' ? $boardSlug : 'board';
        $topicTitle = (string) ($ctx->topic['title'] ?? 'Topic');
        $topicId = (int) ($ctx->topic['id'] ?? 0);
        $topicUrl = $this->topicUrl($topicId);
        $pending = ($ctx->post['approval_status'] ?? 'approved') === 'pending';

        if ($ctx->kind === 'topic') {
            $headline = $pending
                ? "*{$author}* started a new topic awaiting approval in *{$boardLabel}*"
                : "*{$author}* started a new topic in *{$boardLabel}*";
            $plain = $pending
                ? "{$author} started a new topic awaiting approval in {$boardLabel}"
                : "{$author} started a new topic in {$boardLabel}";
        } else {
            $headline = $pending
                ? "*{$author}* replied (pending approval) in *{$topicTitle}*"
                : "*{$author}* replied in *{$topicTitle}*";
            $plain = $pending
                ? "{$author} replied (pending approval) in {$topicTitle}"
                : "{$author} replied in {$topicTitle}";
        }

        $lines = [
            "{$headline}: <{$topicUrl}|{$topicTitle}>",
        ];

        $plainLines = [
            "{$plain}: {$topicTitle} — {$topicUrl}",
        ];

        if ($this->settings->includeExcerpt && $ctx->body !== '') {
            $excerpt = $this->excerpt($ctx->body);
            if ($excerpt !== '') {
                $lines[] = "> {$excerpt}";
                $plainLines[] = $excerpt;
            }
        }

        return [
            'text' => implode("\n", $lines),
            'discord_content' => implode("\n", $plainLines),
        ];
    }

    /**
     * @param array<string, mixed> $user
     * @return array{text: string, discord_content: string}
     */
    public function forRegistration(array $user): array
    {
        $username = (string) ($user['username'] ?? 'member');
        $text = "New member registered: *{$username}*";

        return [
            'text' => $text,
            'discord_content' => "New member registered: {$username}",
        ];
    }

    private function topicUrl(int $topicId): string
    {
        if ($topicId <= 0) {
            return rtrim($this->siteUrl, '/') . '/';
        }

        return rtrim($this->siteUrl, '/') . '/topic/' . $topicId;
    }

    private function excerpt(string $body, int $max = 240): string
    {
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $body) ?? $body;
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;
        $text = preg_replace('/[#*_~>|]/', '', $text) ?? $text;
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        if ($text === '') {
            return '';
        }

        if (strlen($text) <= $max) {
            return $text;
        }

        return rtrim(substr($text, 0, $max - 1)) . '…';
    }
}