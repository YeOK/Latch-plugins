<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\AvatarUrl;

/**
 * Validates member-supplied avatar URLs for browser &lt;img&gt; use (not server-side fetch).
 */
final class AvatarUrlValidator
{
    public const MAX_LENGTH = 500;

    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    /**
     * @return array{ok: true, url: string}|array{ok: false, error: string}
     */
    public function validate(string $url): array
    {
        $url = trim($url);
        if ($url === '') {
            return ['ok' => true, 'url' => ''];
        }

        if (strlen($url) > self::MAX_LENGTH) {
            return ['ok' => false, 'error' => 'Avatar URL must be at most ' . self::MAX_LENGTH . ' characters.'];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => 'Avatar URL must be a valid HTTPS address.'];
        }

        if (!str_starts_with(strtolower($url), 'https://')) {
            return ['ok' => false, 'error' => 'Avatar URL must use HTTPS.'];
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return ['ok' => false, 'error' => 'Avatar URL must be a valid HTTPS address.'];
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return ['ok' => false, 'error' => 'Avatar URL must include a hostname.'];
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return ['ok' => false, 'error' => 'Avatar URL must not target private or reserved addresses.'];
        }

        $ipLiteral = $host;
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $ipLiteral = substr($host, 1, -1);
        }
        if (filter_var($ipLiteral, FILTER_VALIDATE_IP)) {
            if (filter_var(
                $ipLiteral,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            ) === false) {
                return ['ok' => false, 'error' => 'Avatar URL must not target private or reserved addresses.'];
            }
        }

        if ($this->settings->allowedHosts === []) {
            return ['ok' => false, 'error' => 'Custom avatars are not configured — an admin must allow image hosts first.'];
        }

        if (!$this->settings->hostAllowed($host)) {
            return ['ok' => false, 'error' => 'That image host is not allowed. Ask an admin to add it to the avatar-url allowlist.'];
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path !== '' && preg_match('/\.(svg|html?|php|js)(\?|$)/i', $path) === 1) {
            return ['ok' => false, 'error' => 'Avatar URL must point to a raster image (JPEG, PNG, GIF, or WebP).'];
        }

        return ['ok' => true, 'url' => $url];
    }
}
