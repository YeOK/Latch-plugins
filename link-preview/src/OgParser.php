<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class OgParser
{
    /**
     * @return array{title: ?string, description: ?string, image_url: ?string, site_name: ?string}
     */
    public static function parse(string $html, string $fallbackUrl): array
    {
        $title = self::metaContent($html, 'og:title')
            ?? self::metaContent($html, 'twitter:title')
            ?? self::titleTag($html);
        $description = self::metaContent($html, 'og:description')
            ?? self::metaContent($html, 'twitter:description')
            ?? self::metaContent($html, 'description');
        $image = self::metaContent($html, 'og:image')
            ?? self::metaContent($html, 'twitter:image');
        $siteName = self::metaContent($html, 'og:site_name');

        if ($image !== null && str_starts_with($image, '/')) {
            $parts = parse_url($fallbackUrl);
            if (is_array($parts) && !empty($parts['scheme']) && !empty($parts['host'])) {
                $image = $parts['scheme'] . '://' . $parts['host'] . $image;
            }
        }

        return [
            'title' => self::cleanText($title),
            'description' => self::cleanText($description),
            'image_url' => $image !== null ? SafeUrl::normalize($image) : null,
            'site_name' => self::cleanText($siteName),
        ];
    }

    private static function metaContent(string $html, string $property): ?string
    {
        $quoted = preg_quote($property, '/');
        $patterns = [
            '/<meta[^>]+property=["\']' . $quoted . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']' . $quoted . '["\'][^>]*>/i',
            '/<meta[^>]+name=["\']' . $quoted . '["\'][^>]+content=["\']([^"\']+)["\'][^>]*>/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']' . $quoted . '["\'][^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m) === 1) {
                return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        }

        return null;
    }

    private static function titleTag(string $html): ?string
    {
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m) !== 1) {
            return null;
        }

        return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > 500 ? mb_substr($value, 0, 497) . '…' : $value;
    }
}