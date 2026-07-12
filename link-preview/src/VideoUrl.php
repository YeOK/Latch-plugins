<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class VideoUrl
{
    public const KIND_YOUTUBE = 'youtube';
    public const KIND_VIMEO = 'vimeo';

    /**
     * @return array{kind: string, id: string}|null
     */
    public static function parse(string $url): ?array
    {
        $youtubeId = self::youtubeId($url);
        if ($youtubeId !== null) {
            return ['kind' => self::KIND_YOUTUBE, 'id' => $youtubeId];
        }

        $vimeoId = self::vimeoId($url);
        if ($vimeoId !== null) {
            return ['kind' => self::KIND_VIMEO, 'id' => $vimeoId];
        }

        return null;
    }

    public static function youtubeId(string $url): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if (str_ends_with($host, 'youtu.be')) {
            $id = trim($path, '/');

            return self::validYoutubeId($id) ? $id : null;
        }

        if (!str_contains($host, 'youtube.com') && !str_contains($host, 'youtube-nocookie.com')) {
            return null;
        }

        parse_str((string) ($parts['query'] ?? ''), $query);
        if (isset($query['v']) && self::validYoutubeId((string) $query['v'])) {
            return (string) $query['v'];
        }

        if (preg_match('#/shorts/([^/?]+)#', $path, $m) === 1 && self::validYoutubeId($m[1])) {
            return $m[1];
        }

        if (preg_match('#/embed/([^/?]+)#', $path, $m) === 1 && self::validYoutubeId($m[1])) {
            return $m[1];
        }

        return null;
    }

    public static function vimeoId(string $url): ?string
    {
        if (preg_match('#vimeo\.com/(?:video/)?(\d{6,})#i', $url, $m) !== 1) {
            return null;
        }

        return $m[1];
    }

    private static function validYoutubeId(string $id): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]{11}$/', $id) === 1;
    }
}