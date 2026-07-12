<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class CardRenderer
{
    private const THUMB_WIDTH = 160;
    private const THUMB_HEIGHT = 120;

    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    public function render(?PreviewRecord $record, string $url, string $label, bool $eagerThumb = false): string
    {
        if ($record === null) {
            return $this->fallbackLink($url, $label);
        }

        if ($this->settings->embedVideos && $record->videoId !== null) {
            $embed = $this->renderEmbed($record);
            if ($embed !== null) {
                return $embed;
            }
        }

        return $this->renderCard($record, $eagerThumb);
    }

    private function renderEmbed(PreviewRecord $record): ?string
    {
        if ($record->kind === VideoUrl::KIND_YOUTUBE) {
            return $this->renderEmbedPlaceholder(
                $record,
                'youtube',
                'https://www.youtube-nocookie.com/embed/' . rawurlencode($record->videoId ?? ''),
                $record->displayTitle(),
            );
        }

        if ($record->kind === VideoUrl::KIND_VIMEO) {
            return $this->renderEmbedPlaceholder(
                $record,
                'vimeo',
                'https://player.vimeo.com/video/' . rawurlencode($record->videoId ?? ''),
                $record->displayTitle(),
            );
        }

        return null;
    }

    /**
     * Placeholder only — embed.js mounts the iframe on play (keeps plugin-audit clean).
     */
    private function renderEmbedPlaceholder(PreviewRecord $record, string $kind, string $src, string $title): string
    {
        $safeSrc = htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $posterAttr = '';
        $imageSrc = $this->imageSrc($record);
        if ($imageSrc !== null) {
            $posterAttr = ' data-embed-poster="' . htmlspecialchars($imageSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return '<div class="link-embed link-embed--' . htmlspecialchars($kind, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-embed-src="' . $safeSrc . '"'
            . ' data-embed-title="' . $safeTitle . '"'
            . $posterAttr
            . ' role="region" aria-label="' . $safeTitle . '">'
            . '<button type="button" class="link-embed-play" aria-label="Play ' . $safeTitle . '">'
            . '<span class="link-embed-play-icon" aria-hidden="true"></span>'
            . '</button>'
            . '<noscript><a class="link-embed-noscript muted" href="' . $safeSrc . '" rel="nofollow ugc" target="_blank">'
            . 'Open video</a></noscript>'
            . '</div>';
    }

    private function renderCard(PreviewRecord $record, bool $eagerThumb): string
    {
        $safeUrl = htmlspecialchars($record->url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $title = htmlspecialchars($record->displayTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $site = htmlspecialchars($record->displaySite(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $thumb = '';
        $imageSrc = $this->imageSrc($record);
        if ($imageSrc !== null) {
            $safeImg = htmlspecialchars($imageSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $imgAttrs = 'decoding="async" width="' . self::THUMB_WIDTH . '" height="' . self::THUMB_HEIGHT . '"';
            if ($eagerThumb) {
                $imgAttrs .= ' fetchpriority="high"';
            } else {
                $imgAttrs .= ' loading="lazy"';
            }

            $thumb = '<div class="link-onebox__thumb-wrap">'
                . '<img class="link-onebox__thumb" src="' . $safeImg . '" alt="" ' . $imgAttrs . '>'
                . '</div>';
        }

        $desc = '';
        if ($record->description !== null && $record->description !== '') {
            $desc = '<span class="link-onebox__desc muted">'
                . htmlspecialchars($record->description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</span>';
        }

        return '<aside class="link-onebox" data-url="' . $safeUrl . '">'
            . '<a class="link-onebox__card" href="' . $safeUrl . '" rel="nofollow ugc" target="_blank">'
            . $thumb
            . '<span class="link-onebox__body">'
            . '<span class="link-onebox__title">' . $title . '</span>'
            . $desc
            . '<span class="link-onebox__site muted">' . $site . '</span>'
            . '</span>'
            . '</a>'
            . '</aside>';
    }

    private function imageSrc(PreviewRecord $record): ?string
    {
        if ($record->imageUrl === null || $record->imageUrl === '') {
            return null;
        }

        return '/plugin/link-preview/image/' . $record->urlHash;
    }

    private function fallbackLink(string $url, string $label): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<a href="' . $safeUrl . '" rel="nofollow ugc" target="_blank">' . $safeLabel . '</a>';
    }
}