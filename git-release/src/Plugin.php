<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

use Latch\Core\Plugins\HookName;
use Latch\Core\Plugins\PluginContext;
use Latch\Core\Plugins\PluginInterface;
use Latch\Core\Plugins\PluginSettingsStore;
use Latch\Core\Response;
use Latch\Core\Router;

final class Plugin implements PluginInterface
{
    public function register(PluginContext $context): void
    {
        $pluginPath = $context->path();
        $assetVersion = $context->app()->assetVersion();
        $pluginVersion = $context->manifest()->version;
        $storageRoot = (string) $context->app()->config()->get('paths.storage');
        $settingsStore = PluginSettingsStore::forPlugin($context->manifest(), $storageRoot);

        $context->hooks()->add(
            HookName::ROUTE_REGISTER,
            static function (Router $router) use ($pluginPath, $settingsStore, $assetVersion, $pluginVersion): void {
                $router->get('/plugin/git-release/widget.json', static function () use ($settingsStore, $assetVersion, $pluginVersion): void {
                    $settings = Settings::fromStored($settingsStore->all());
                    $html = (new ReleaseWidget($assetVersion, $pluginVersion))->renderHtml($settings);
                    Response::json(['html' => $html], 200, $settings->maxAgeSeconds);
                });

                $cssPath = $pluginPath . '/assets/widget.css';
                $router->get('/plugin/git-release/widget.css', static function () use ($cssPath, $assetVersion): void {
                    if (!is_file($cssPath)) {
                        Response::notFound();

                        return;
                    }

                    $etag = '"' . hash('sha256', $cssPath . '|' . filemtime($cssPath) . '|' . $assetVersion) . '"';
                    http_response_code(200);
                    header('Content-Type: text/css; charset=utf-8');
                    header('Cache-Control: public, max-age=31536000, immutable');
                    header('ETag: ' . $etag);

                    $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
                    if (is_string($ifNoneMatch) && trim($ifNoneMatch) === $etag) {
                        http_response_code(304);
                        exit;
                    }

                    readfile($cssPath);
                    exit;
                });
            },
        );

        // CSS is injected with widget HTML (client-mode plugins cannot use theme.assets until core
        // skips client placeholders for asset hooks — see PluginCacheCoordinator).
        // home.before_boards is listed for placement; client cache mode serves a placeholder instead.
        $context->hooks()->add(
            HookName::HOME_BEFORE_BOARDS,
            static fn (): string => '',
        );
    }
}