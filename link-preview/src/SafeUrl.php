<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

use Latch\Support\OutboundUrlGuard;

final class SafeUrl
{
    public static function normalize(string $url): ?string
    {
        return OutboundUrlGuard::normalizePublicHttpsUrl($url);
    }

    public static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? strtolower($host) : null;
    }
}