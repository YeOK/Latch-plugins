<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

final class Settings
{
    public function __construct(
        public readonly bool $enabled,
        public readonly bool $embedVideos,
        public readonly int $maxPreviews,
        public readonly int $fetchTimeout,
        public readonly int $cacheTtlHours,
    ) {
    }

    public static function load(string $storageRoot, PluginManifest $manifest): self
    {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();

        $maxPreviews = (int) ($values['max_previews'] ?? 3);
        $fetchTimeout = (int) ($values['fetch_timeout'] ?? 5);
        $cacheTtlHours = (int) ($values['cache_ttl_hours'] ?? 168);

        return new self(
            enabled: (bool) ($values['enabled'] ?? true),
            embedVideos: (bool) ($values['embed_videos'] ?? true),
            maxPreviews: max(1, min(10, $maxPreviews)),
            fetchTimeout: max(2, min(15, $fetchTimeout)),
            cacheTtlHours: max(1, min(720, $cacheTtlHours)),
        );
    }
}