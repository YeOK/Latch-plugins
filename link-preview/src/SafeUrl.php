<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class SafeUrl
{
    public static function normalize(string $url): ?string
    {
        $url = trim($url);
        if ($url === '' || !preg_match('/^https:\/\//i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            if (!self::isPublicIp($host)) {
                return null;
            }
        } else {
            $resolved = gethostbyname($host);
            if ($resolved !== $host && !self::isPublicIp($resolved)) {
                return null;
            }
        }

        return $url;
    }

    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }

    private static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }
}