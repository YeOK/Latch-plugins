<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\FediverseShare;

/**
 * Renders the topic header share control (HTML + data attributes for JS).
 */
final class SharePanel
{
    public function __construct(
        private readonly Settings $settings,
    ) {
    }

    /**
     * @param array<string, mixed> $topic
     */
    public function render(string $siteName, string $siteUrl, array $topic): string
    {
        if (!$this->settings->enabled || !$this->settings->hasAnyAction()) {
            return '';
        }

        $topicId = (int) ($topic['id'] ?? 0);
        if ($topicId < 1) {
            return '';
        }

        $title = trim((string) ($topic['title'] ?? 'Topic'));
        $slug = trim((string) ($topic['slug'] ?? ''));
        $url = ShareUrlBuilder::topicUrl($siteUrl, $topicId, $slug);
        $text = ShareUrlBuilder::formatShareText($this->settings->shareTemplate, [
            'title' => $title,
            'url' => $url,
            'site' => $siteName,
        ]);

        $defaultInstance = $this->settings->defaultInstance;
        $presets = $this->settings->presetInstances;

        $datalistId = 'latch-fedi-instances-' . $topicId;
        $options = '';
        foreach ($presets as $host) {
            $options .= '<option value="' . self::h($host) . '">';
        }

        $actions = '';
        if ($this->settings->showMastodon) {
            $actions .= '<button type="button" class="btn btn-small latch-fedi-share__btn" data-fedi-action="mastodon">'
                . 'Mastodon</button>';
        }
        if ($this->settings->showMisskey) {
            $actions .= '<button type="button" class="btn btn-small latch-fedi-share__btn" data-fedi-action="misskey">'
                . 'Misskey</button>';
        }
        if ($this->settings->showCopyLink) {
            $actions .= '<button type="button" class="btn btn-small latch-fedi-share__btn" data-fedi-action="copy">'
                . 'Copy link</button>';
        }
        if ($this->settings->showWebShare) {
            $actions .= '<button type="button" class="btn btn-small latch-fedi-share__btn latch-fedi-share__web" data-fedi-action="web" hidden>'
                . 'Share…</button>';
        }

        $preview = nl2br(self::h($text), false);

        $icon = '<svg class="latch-fedi-share__icon" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
            . '<path fill="currentColor" d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>'
            . '</svg>';

        return '<details class="latch-fedi-share" data-latch-fedi-share'
            . ' data-share-text="' . self::h($text) . '"'
            . ' data-share-url="' . self::h($url) . '"'
            . ' data-share-title="' . self::h($title) . '"'
            . ' data-default-instance="' . self::h($defaultInstance) . '">'
            . '<summary class="btn btn-icon field-haze-btn latch-fedi-share__summary"'
            . ' title="Share to the Fediverse" aria-label="Share to the Fediverse">'
            . $icon
            . '</summary>'
            . '<div class="latch-fedi-share__panel" role="dialog" aria-label="Share topic">'
            . '<p class="latch-fedi-share__label muted">Share this topic</p>'
            . '<div class="latch-fedi-share__preview">' . $preview . '</div>'
            . '<label class="latch-fedi-share__field">'
            . '<span class="muted">Your instance</span>'
            . '<input type="text" class="latch-fedi-share__instance" name="fedi_instance"'
            . ' list="' . self::h($datalistId) . '"'
            . ' placeholder="mastodon.social" autocomplete="off" spellcheck="false"'
            . ' value="' . self::h($defaultInstance) . '">'
            . '</label>'
            . '<datalist id="' . self::h($datalistId) . '">' . $options . '</datalist>'
            . '<div class="latch-fedi-share__actions">' . $actions . '</div>'
            . '<p class="latch-fedi-share__status muted" data-fedi-status hidden></p>'
            . '</div>'
            . '</details>';
    }

    private static function h(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
