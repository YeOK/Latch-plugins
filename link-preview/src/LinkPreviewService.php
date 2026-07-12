<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class LinkPreviewService
{
    public function __construct(
        private readonly Settings $settings,
        private readonly MetadataResolver $resolver,
        private readonly CardRenderer $renderer,
    ) {
    }

    public function formatLink(string $html, string $url, string $label, bool $standalone): string
    {
        if (!$this->settings->enabled || !$standalone) {
            return $html;
        }

        if (!PreviewLimiter::canExpand($this->settings->maxPreviews)) {
            return $html;
        }

        $safeUrl = SafeUrl::normalize($url);
        if ($safeUrl === null) {
            return $html;
        }

        $eagerThumb = PreviewLimiter::expansionCount() === 0;
        PreviewLimiter::recordExpansion();

        try {
            $record = $this->resolver->resolve($safeUrl);
        } catch (\Throwable) {
            return $html;
        }

        return $this->renderer->render($record, $safeUrl, $label, $eagerThumb);
    }
}