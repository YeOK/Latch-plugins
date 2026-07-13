<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\PrivacyAnalytics;

use Latch\Core\Application;
use Latch\Core\Plugins\HookName;
use Latch\Core\Plugins\PluginContext;
use Latch\Core\Plugins\PluginInterface;

final class Plugin implements PluginInterface
{
    public function register(PluginContext $context): void
    {
        $app = $context->app();
        $storageRoot = (string) $app->config()->get('paths.storage');
        $settings = Settings::load($storageRoot, $context->manifest());
        $snippet = new AnalyticsSnippet($settings);

        $context->hooks()->add(
            HookName::LAYOUT_HEAD,
            static function (Application $hookApp) use ($snippet, $settings): string {
                if ($settings->guestsOnly && $hookApp->auth()->check()) {
                    return '';
                }

                return $snippet->renderHead($hookApp->cspNonce());
            },
        );

        $context->hooks()->add(
            HookName::CSP_SCRIPT_SRC,
            static function () use ($snippet): string {
                return $snippet->cspScriptHost();
            },
        );

        $context->hooks()->add(
            HookName::CSP_CONNECT_SRC,
            static function () use ($snippet): string {
                return $snippet->cspScriptHost();
            },
        );
    }
}