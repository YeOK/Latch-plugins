<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

use Latch\Core\Application;

/**
 * Webhook URL from config/local.php → plugins.slack_notify (never in DB/JSON).
 */
final class PluginConfig
{
    public function __construct(
        public readonly ?string $webhookUrl,
    ) {
    }

    public static function fromApp(Application $app): self
    {
        $raw = $app->config()->get('plugins.slack_notify');
        if (!is_array($raw)) {
            return new self(null);
        }

        $url = trim((string) ($raw['webhook_url'] ?? ''));
        if ($url === '') {
            return new self(null);
        }

        return new self($url);
    }

    public function isConfigured(): bool
    {
        return $this->webhookUrl !== null && $this->webhookUrl !== '';
    }
}