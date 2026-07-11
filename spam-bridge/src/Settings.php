<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

final class Settings
{
    public const PROVIDER_AKISMET = 'akismet';
    public const PROVIDER_SFS = 'stop_forum_spam';
    public const PROVIDER_BOTH = 'both';

    public function __construct(
        public readonly string $provider,
        public readonly int $strictness,
        public readonly int $sfsMinConfidence,
        public readonly bool $staffBypass,
        public readonly bool $checkRegistrations,
        public readonly bool $logRejects,
    ) {
    }

    public static function load(string $storageRoot, PluginManifest $manifest): self
    {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();

        $provider = (string) ($values['provider'] ?? self::PROVIDER_AKISMET);
        if (!in_array($provider, [self::PROVIDER_AKISMET, self::PROVIDER_SFS, self::PROVIDER_BOTH], true)) {
            $provider = self::PROVIDER_AKISMET;
        }

        $strictness = (int) ($values['strictness'] ?? 1);
        $strictness = max(0, min(3, $strictness));

        $sfsMinConfidence = (int) ($values['sfs_min_confidence'] ?? 90);
        $sfsMinConfidence = max(1, min(100, $sfsMinConfidence));

        return new self(
            provider: $provider,
            strictness: $strictness,
            sfsMinConfidence: $sfsMinConfidence,
            staffBypass: (bool) ($values['staff_bypass'] ?? true),
            checkRegistrations: (bool) ($values['check_registrations'] ?? true),
            logRejects: (bool) ($values['log_rejects'] ?? true),
        );
    }

    public function usesAkismet(): bool
    {
        return $this->provider === self::PROVIDER_AKISMET || $this->provider === self::PROVIDER_BOTH;
    }

    public function usesStopForumSpam(): bool
    {
        return $this->provider === self::PROVIDER_SFS || $this->provider === self::PROVIDER_BOTH;
    }
}