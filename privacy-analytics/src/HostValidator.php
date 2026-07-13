<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\PrivacyAnalytics;

final class HostValidator
{
    public static function normalizeHost(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('#^https?://#i', '', $value) ?? $value;
        $value = rtrim($value, '/');
        if (str_contains($value, '/')) {
            $value = (string) parse_url('https://' . $value, PHP_URL_HOST);
        }

        return self::isValidHostname($value) ? $value : '';
    }

    public static function isValidDomain(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        return $domain !== '' && self::isValidHostname($domain);
    }

    public static function httpsBaseUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (!preg_match('#^https://#i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $host = strtolower((string) $parts['host']);
        if (!self::isValidHostname($host)) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';

        return 'https://' . $host . $port . '/';
    }

    private static function isValidHostname(string $host): bool
    {
        if ($host === '' || strlen($host) > 253) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return false;
        }

        return (bool) preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host);
    }
}