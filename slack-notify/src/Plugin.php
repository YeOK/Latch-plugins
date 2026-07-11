<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

use Latch\Core\Plugins\HookName;
use Latch\Core\Plugins\PluginContext;
use Latch\Core\Plugins\PluginInterface;
use Latch\Core\Plugins\PostSaveContext;

final class Plugin implements PluginInterface
{
    public function register(PluginContext $context): void
    {
        $app = $context->app();
        $storageRoot = (string) $app->config()->get('paths.storage');
        $settings = Settings::load($storageRoot, $context->manifest());
        $config = PluginConfig::fromApp($app);
        $messages = new MessageBuilder($settings, $app->siteUrl());
        $client = new WebhookClient(new HttpTransport());
        $notifier = new Notifier($settings, $config, $messages, $client);

        $context->hooks()->add(
            HookName::POST_AFTER_SAVE,
            static function (PostSaveContext $ctx) use ($notifier): void {
                $notifier->onPostSaved($ctx);
            },
            10,
        );

        $context->hooks()->add(
            HookName::USER_REGISTER,
            static function (array $user) use ($notifier): void {
                $notifier->onUserRegistered($user);
            },
            20,
        );
    }
}