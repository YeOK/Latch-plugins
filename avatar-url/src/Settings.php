<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\AvatarUrl;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

final class Settings
{
    /**
     * @param list<string> $allowedHosts
     */
    public function __construct(
        public readonly array $allowedHosts,
        public readonly bool $membersCanSet,
    ) {
    }

    public static function load(string $storageRoot, PluginManifest $manifest): self
    {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();

        $hosts = $values['allowed_hosts'] ?? [];
        if (!is_array($hosts)) {
            $hosts = [];
        }

        $normalized = [];
        foreach ($hosts as $entry) {
            if (!is_string($entry)) {
                continue;
            }
            $host = strtolower(trim($entry));
            $host = preg_replace('#^https?://#', '', $host) ?? $host;
            $host = rtrim(explode('/', $host)[0] ?? '', '.');
            if ($host === '' || strlen($host) > 253) {
                continue;
            }
            $normalized[] = $host;
        }

        return new self(
            allowedHosts: array_values(array_unique($normalized)),
            membersCanSet: (bool) ($values['members_can_set'] ?? true),
        );
    }

    public function hostAllowed(string $host): bool
    {
        $host = strtolower(trim($host));
        if ($host === '' || $this->allowedHosts === []) {
            return false;
        }

        foreach ($this->allowedHosts as $rule) {
            if ($rule === $host) {
                return true;
            }
            if (str_starts_with($rule, '*.') && strlen($rule) > 2) {
                $suffix = substr($rule, 1);
                if ($host === substr($rule, 2) || str_ends_with($host, $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
