<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class PreviewRecord
{
    public function __construct(
        public readonly string $url,
        public readonly string $urlHash,
        public readonly string $kind,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $imageUrl,
        public readonly ?string $siteName,
        public readonly ?string $videoId = null,
    ) {
    }

    public function displayTitle(): string
    {
        if ($this->title !== null && $this->title !== '') {
            return $this->title;
        }

        return $this->url;
    }

    public function displaySite(): string
    {
        if ($this->siteName !== null && $this->siteName !== '') {
            return $this->siteName;
        }

        return SafeUrl::host($this->url) ?? $this->url;
    }
}