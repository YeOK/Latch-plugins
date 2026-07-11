<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

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
        $config = PluginConfig::fromApp($app);

        $transport = new HttpTransport();
        $akismet = $config->hasAkismet()
            ? new AkismetClient($config->akismetApiKey ?? '', $transport)
            : null;
        $sfs = new StopForumSpamClient($transport);
        $log = new SpamLog($context->database());
        $checker = new SpamChecker(
            $storageRoot,
            $context->manifest(),
            $config,
            $akismet,
            $sfs,
            $log,
            $app->request(),
            $app->siteUrl(),
            new AppRegistrationEnforcer($app),
        );

        $context->hooks()->add(
            HookName::POST_BEFORE_SAVE,
            static function (PostSaveContext $ctx) use ($checker): void {
                $reason = $checker->checkPost($ctx);
                if ($reason !== null) {
                    $ctx->reject($reason);
                }
            },
            15,
        );

        $context->hooks()->add(
            HookName::USER_REGISTER,
            static function (array $user) use ($checker): void {
                $checker->checkRegistration($user);
            },
            10,
        );
    }
}