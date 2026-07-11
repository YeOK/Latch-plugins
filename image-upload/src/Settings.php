<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\ImageUpload;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

/**
 * Non-secret operator toggles from storage/plugins/image-upload/settings.json.
 */
final class Settings
{
    /** @var array<string, string> */
    public const ALL_CONTENT_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    /** @var list<string> */
    private const DEFAULT_ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * @param list<string> $allowedTypes MIME types enabled for upload.
     */
    public function __construct(
        public readonly int $maxMb,
        public readonly string $keyPrefix,
        public readonly array $allowedTypes,
    ) {
    }

    /**
     * @param array<string, mixed>|null $legacyLocal Deprecated keys from config/local.php (pre-PR-P4).
     */
    public static function load(
        string $storageRoot,
        PluginManifest $manifest,
        ?array $legacyLocal = null,
    ): self {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();
        $hasSettingsFile = is_file($store->settingsPath());

        $maxMb = (int) ($values['max_mb'] ?? 8);
        if (!$hasSettingsFile && $legacyLocal !== null && array_key_exists('max_mb', $legacyLocal)) {
            $maxMb = (int) $legacyLocal['max_mb'];
        }
        $maxMb = max(1, min(32, $maxMb));

        $prefix = trim((string) ($values['key_prefix'] ?? 'forum/'));
        if (!$hasSettingsFile && $legacyLocal !== null && array_key_exists('key_prefix', $legacyLocal)) {
            $prefix = trim((string) $legacyLocal['key_prefix']);
        }
        if ($prefix !== '' && !str_ends_with($prefix, '/')) {
            $prefix .= '/';
        }

        $allowed = $values['allowed_types'] ?? self::DEFAULT_ALLOWED_TYPES;
        if (!$hasSettingsFile && $legacyLocal !== null && is_array($legacyLocal['allowed_types'] ?? null)) {
            $allowed = $legacyLocal['allowed_types'];
        }
        if (!is_array($allowed) || $allowed === []) {
            $allowed = self::DEFAULT_ALLOWED_TYPES;
        }

        $allowedTypes = [];
        foreach ($allowed as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            $entry = strtolower(trim($entry));
            if (isset(self::ALL_CONTENT_TYPES[$entry])) {
                $allowedTypes[] = $entry;
            }
        }

        if ($allowedTypes === []) {
            $allowedTypes = self::DEFAULT_ALLOWED_TYPES;
        }

        return new self(
            maxMb: $maxMb,
            keyPrefix: $prefix,
            allowedTypes: array_values(array_unique($allowedTypes)),
        );
    }

    public function maxBytes(): int
    {
        return $this->maxMb * 1024 * 1024;
    }

    /**
     * @return array<string, string>
     */
    public function allowedTypeMap(): array
    {
        $map = [];
        foreach ($this->allowedTypes as $mime) {
            if (isset(self::ALL_CONTENT_TYPES[$mime])) {
                $map[$mime] = self::ALL_CONTENT_TYPES[$mime];
            }
        }

        return $map !== [] ? $map : self::ALL_CONTENT_TYPES;
    }
}