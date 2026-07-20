<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\FediverseShare;

/**
 * Build Fediverse share URLs and normalize instance hostnames.
 */
final class ShareUrlBuilder
{
    /**
     * Hostname only (lowercase), or null if invalid / private-looking.
     */
    public static function normalizeInstance(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('#^https?://#i', '', $raw) ?? $raw;
        $raw = explode('/', $raw)[0] ?? '';
        $raw = explode('?', $raw)[0] ?? '';
        $raw = strtolower(rtrim($raw, '.'));

        if ($raw === '' || strlen($raw) > 253) {
            return null;
        }

        // DNS hostname (at least one dot) or localhost for local testing.
        $okDns = (bool) preg_match(
            '/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
            $raw,
        );
        $okLocal = $raw === 'localhost' || (bool) preg_match('/^localhost:\d{1,5}$/', $raw);
        if (!$okDns && !$okLocal) {
            return null;
        }

        // Block literal private IPs if someone pastes them as "hosts".
        if (preg_match('/^\d{1,3}(\.\d{1,3}){3}/', $raw)) {
            return null;
        }

        return $raw;
    }

    /**
     * @param array{title: string, url: string, site: string} $vars
     */
    public static function formatShareText(string $template, array $vars): string
    {
        $template = str_replace(['\\n', "\r\n", "\r"], "\n", $template);
        if (trim($template) === '') {
            $template = "{title}\n{url}";
        }

        $text = str_replace(
            ['{title}', '{url}', '{site}'],
            [
                $vars['title'],
                $vars['url'],
                $vars['site'],
            ],
            $template,
        );

        // Collapse excessive blank lines; keep intentional single newlines.
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        // If template lost separators (e.g. "{title}{url}"), keep text readable.
        $title = $vars['title'];
        $url = $vars['url'];
        if ($title !== '' && $url !== '' && !str_contains($text, "\n") && str_contains($text, $title . $url)) {
            $text = $title . "\n" . $url;
        }

        return $text;
    }

    public static function mastodonShareUrl(string $instance, string $text): string
    {
        $host = self::normalizeInstance($instance);
        if ($host === null) {
            return '';
        }

        return 'https://' . $host . '/share?text=' . rawurlencode($text);
    }

    public static function misskeyShareUrl(string $instance, string $text): string
    {
        $host = self::normalizeInstance($instance);
        if ($host === null) {
            return '';
        }

        // Misskey / Firefish style share intent
        return 'https://' . $host . '/share?text=' . rawurlencode($text);
    }

    public static function topicUrl(string $siteUrl, int $topicId, string $slug = ''): string
    {
        $base = rtrim($siteUrl, '/');
        if ($slug !== '') {
            return $base . '/topic/' . rawurlencode($slug);
        }

        return $base . '/topic/' . $topicId;
    }
}
