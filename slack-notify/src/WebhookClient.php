<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

use Latch\Support\OutboundUrlGuard;

/**
 * Delivers JSON payloads to Slack or Discord incoming webhooks.
 */
final class WebhookClient
{
    public function __construct(
        private readonly HttpTransport $transport,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function send(string $webhookUrl, array $payload): bool
    {
        $error = OutboundUrlGuard::publicHttpsUrlError($webhookUrl);
        if ($error !== null) {
            return false;
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $response = $this->transport->postJson($webhookUrl, $json, [
            'User-Agent: Latch-SlackNotify/1.0',
        ]);

        return $response !== null;
    }

    public static function isDiscordWebhook(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'discord.com' || $host === 'discordapp.com';
    }
}