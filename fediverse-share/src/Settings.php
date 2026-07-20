<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\FediverseShare;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

final class Settings
{
    /** @var list<string> */
    public const DEFAULT_PRESETS = [
        'mastodon.social',
        'mastodon.online',
        'fosstodon.org',
        'hachyderm.io',
        'misskey.io',
    ];

    /**
     * @param list<string> $presetInstances
     */
    public function __construct(
        public readonly bool $enabled,
        public readonly string $defaultInstance,
        public readonly string $shareTemplate,
        public readonly array $presetInstances,
        public readonly bool $showMastodon,
        public readonly bool $showMisskey,
        public readonly bool $showCopyLink,
        public readonly bool $showWebShare,
    ) {
    }

    public static function load(string $storageRoot, PluginManifest $manifest): self
    {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();

        $template = trim((string) ($values['share_template'] ?? '{title}\n{url}'));
        if ($template === '') {
            $template = "{title}\n{url}";
        }
        // Settings UI may store literal \n
        $template = str_replace(['\\n', "\r\n"], ["\n", "\n"], $template);

        $presets = $values['preset_instances'] ?? [];
        if (!is_array($presets)) {
            $presets = [];
        }

        $normalizedPresets = [];
        foreach ($presets as $entry) {
            if (!is_string($entry)) {
                continue;
            }
            $host = ShareUrlBuilder::normalizeInstance($entry);
            if ($host !== null) {
                $normalizedPresets[] = $host;
            }
        }
        $normalizedPresets = array_values(array_unique($normalizedPresets));
        if ($normalizedPresets === []) {
            $normalizedPresets = self::DEFAULT_PRESETS;
        }

        $default = ShareUrlBuilder::normalizeInstance((string) ($values['default_instance'] ?? '')) ?? '';

        return new self(
            enabled: (bool) ($values['enabled'] ?? true),
            defaultInstance: $default,
            shareTemplate: $template,
            presetInstances: $normalizedPresets,
            showMastodon: (bool) ($values['show_mastodon'] ?? true),
            showMisskey: (bool) ($values['show_misskey'] ?? true),
            showCopyLink: (bool) ($values['show_copy_link'] ?? true),
            showWebShare: (bool) ($values['show_web_share'] ?? true),
        );
    }

    public function hasAnyAction(): bool
    {
        return $this->showMastodon
            || $this->showMisskey
            || $this->showCopyLink
            || $this->showWebShare;
    }
}
