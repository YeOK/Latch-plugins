<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

use Latch\Core\Application;

/**
 * Operator secrets from config/local.php → plugins.spam_bridge (never in DB/JSON).
 */
final class PluginConfig
{
    public function __construct(
        public readonly ?string $akismetApiKey,
    ) {
    }

    public static function fromApp(Application $app): self
    {
        $raw = $app->config()->get('plugins.spam_bridge');
        if (!is_array($raw)) {
            return new self(null);
        }

        $akismetKey = trim((string) ($raw['akismet_api_key'] ?? ''));
        if ($akismetKey === '') {
            return new self(null);
        }

        return new self($akismetKey);
    }

    public function hasAkismet(): bool
    {
        return $this->akismetApiKey !== null && $this->akismetApiKey !== '';
    }
}