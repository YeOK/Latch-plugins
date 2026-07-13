<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\PrivacyAnalytics;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

final class Settings
{
    public const PROVIDER_PLAUSIBLE = 'plausible';
    public const PROVIDER_MATOMO = 'matomo';

    public function __construct(
        public readonly string $provider,
        public readonly string $siteDomain,
        public readonly string $scriptHost,
        public readonly string $matomoUrl,
        public readonly string $matomoSiteId,
        public readonly bool $guestsOnly,
    ) {
    }

    public static function load(string $storageRoot, PluginManifest $manifest): self
    {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();

        $provider = (string) ($values['provider'] ?? self::PROVIDER_PLAUSIBLE);
        if (!in_array($provider, [self::PROVIDER_PLAUSIBLE, self::PROVIDER_MATOMO], true)) {
            $provider = self::PROVIDER_PLAUSIBLE;
        }

        $scriptHost = HostValidator::normalizeHost((string) ($values['script_host'] ?? 'plausible.io'));
        if ($scriptHost === '') {
            $scriptHost = 'plausible.io';
        }

        return new self(
            provider: $provider,
            siteDomain: trim((string) ($values['site_domain'] ?? '')),
            scriptHost: $scriptHost,
            matomoUrl: trim((string) ($values['matomo_url'] ?? '')),
            matomoSiteId: trim((string) ($values['matomo_site_id'] ?? '')),
            guestsOnly: (bool) ($values['guests_only'] ?? true),
        );
    }

    public function isConfigured(): bool
    {
        return match ($this->provider) {
            self::PROVIDER_PLAUSIBLE => HostValidator::isValidDomain($this->siteDomain),
            self::PROVIDER_MATOMO => HostValidator::httpsBaseUrl($this->matomoUrl) !== null
                && $this->matomoSiteId !== ''
                && ctype_digit($this->matomoSiteId),
            default => false,
        };
    }

    public function cspScriptHost(): string
    {
        if (!$this->isConfigured()) {
            return '';
        }

        return match ($this->provider) {
            self::PROVIDER_PLAUSIBLE => $this->scriptHost,
            self::PROVIDER_MATOMO => (string) parse_url(
                HostValidator::httpsBaseUrl($this->matomoUrl) ?? '',
                PHP_URL_HOST,
            ),
            default => '',
        };
    }
}