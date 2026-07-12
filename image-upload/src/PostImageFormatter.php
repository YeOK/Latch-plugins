<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\ImageUpload;

final class PostImageFormatter
{
    public function __construct(
        private readonly PluginConfig $config,
    ) {
    }

    public function format(string $html): string
    {
        if (!str_contains($html, 'class="post-image"') || str_contains($html, 'post-image-figure')) {
            return $html;
        }

        $host = preg_quote($this->config->publicHost, '/');
        $pattern = '/<img src="(https:\/\/' . $host . '[^"]*)" alt="((?:[^"\\\\]|\\\\.)*)" class="post-image" loading="lazy" decoding="async">/';

        return preg_replace_callback(
            $pattern,
            fn (array $matches): string => $this->wrapImage($matches[1], $matches[2]),
            $html,
        ) ?? $html;
    }

    private function wrapImage(string $url, string $alt): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<figure class="post-image-figure">'
            . '<button type="button" class="post-image-open" data-full-src="' . $safeUrl . '"'
            . ' aria-label="View full size: ' . $safeAlt . '">'
            . '<img src="' . $safeUrl . '" alt="' . $safeAlt . '" class="post-image post-image--preview"'
            . ' loading="lazy" decoding="async">'
            . '</button>'
            . '<noscript><a class="post-image-noscript muted" href="' . $safeUrl . '" rel="nofollow ugc"'
            . ' target="_blank">Open full size image</a></noscript>'
            . '</figure>';
    }
}