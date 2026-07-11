<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

use Latch\Core\Plugins\PostSaveContext;
use Latch\Models\PostRepository;

final class Notifier
{
    public function __construct(
        private readonly Settings $settings,
        private readonly PluginConfig $config,
        private readonly MessageBuilder $messages,
        private readonly WebhookClient $client,
    ) {
    }

    public function onPostSaved(PostSaveContext $ctx): void
    {
        if (!$this->config->isConfigured()) {
            return;
        }

        if ($ctx->post === null || $ctx->topic === null) {
            return;
        }

        if ($ctx->kind === 'edit') {
            return;
        }

        $approval = (string) ($ctx->post['approval_status'] ?? PostRepository::APPROVAL_APPROVED);
        if ($approval === PostRepository::APPROVAL_PENDING && !$this->settings->notifyPending) {
            return;
        }

        if ($approval === PostRepository::APPROVAL_REJECTED) {
            return;
        }

        $event = $ctx->kind === 'topic' ? Settings::EVENT_NEW_TOPIC : Settings::EVENT_NEW_REPLY;
        if (!$this->settings->wantsEvent($event)) {
            return;
        }

        $built = $this->messages->forPost($ctx);
        $this->deliver($built);
    }

    /**
     * @param array<string, mixed> $user
     */
    public function onUserRegistered(array $user): void
    {
        if (!$this->config->isConfigured()) {
            return;
        }

        if (!$this->settings->wantsEvent(Settings::EVENT_USER_REGISTERED)) {
            return;
        }

        $built = $this->messages->forRegistration($user);
        $this->deliver($built);
    }

    /**
     * @param array{text: string, discord_content: string} $built
     */
    private function deliver(array $built): void
    {
        $url = $this->config->webhookUrl;
        if ($url === null || $url === '') {
            return;
        }

        if (WebhookClient::isDiscordWebhook($url)) {
            $payload = [
                'username' => $this->settings->botName,
                'content' => $built['discord_content'],
            ];
        } else {
            $payload = [
                'username' => $this->settings->botName,
                'text' => $built['text'],
                'unfurl_links' => false,
                'unfurl_media' => false,
            ];
        }

        $this->client->send($url, $payload);
    }
}