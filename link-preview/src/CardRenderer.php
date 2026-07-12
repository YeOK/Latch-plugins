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
    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    public function render(?PreviewRecord $record, string $url, string $label): string
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

        return $this->renderCard($record);
    }

    private function renderEmbed(PreviewRecord $record): ?string
    {
        if ($record->kind === VideoUrl::KIND_YOUTUBE) {
            $src = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($record->videoId ?? '');
            $title = htmlspecialchars($record->displayTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return '<div class="link-embed link-embed--youtube">'
                . '<iframe src="' . $src . '" title="' . $title . '" loading="lazy" '
                . 'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" '
                . 'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'
                . '</div>';
        }

        if ($record->kind === VideoUrl::KIND_VIMEO) {
            $src = 'https://player.vimeo.com/video/' . rawurlencode($record->videoId ?? '');
            $title = htmlspecialchars($record->displayTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            return '<div class="link-embed link-embed--vimeo">'
                . '<iframe src="' . $src . '" title="' . $title . '" loading="lazy" '
                . 'allow="autoplay; fullscreen; picture-in-picture" '
                . 'referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>'
                . '</div>';
        }

        return null;
    }

    private function renderCard(PreviewRecord $record): string
    {
        $safeUrl = htmlspecialchars($record->url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $title = htmlspecialchars($record->displayTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $site = htmlspecialchars($record->displaySite(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $thumb = '';
        $imageSrc = $this->imageSrc($record);
        if ($imageSrc !== null) {
            $safeImg = htmlspecialchars($imageSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $thumb = '<div class="link-onebox__thumb-wrap">'
                . '<img class="link-onebox__thumb" src="' . $safeImg . '" alt="" loading="lazy" decoding="async">'
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

        $host = SafeUrl::host($record->imageUrl);
        if ($host === 'i.ytimg.com' || $host === 'i.vimeocdn.com') {
            return $record->imageUrl;
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