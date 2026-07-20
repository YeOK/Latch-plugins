<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\FediverseShare;

use Latch\Core\Application;
use Latch\Core\Plugins\HookName;
use Latch\Core\Plugins\PluginContext;
use Latch\Core\Plugins\PluginInterface;
use Latch\Core\Response;
use Latch\Core\Router;

final class Plugin implements PluginInterface
{
    public function register(PluginContext $context): void
    {
        $app = $context->app();
        $storageRoot = (string) $app->config()->get('paths.storage');
        $settings = Settings::load($storageRoot, $context->manifest());
        $panel = new SharePanel($settings);

        $pluginPath = $context->path();
        $cssPath = $pluginPath . '/assets/share.css';
        $jsPath = $pluginPath . '/assets/share.js';
        // Bust CDN/browser cache on plugin file changes (not only app version).
        $assetVersion = $app->assetVersion()
            . '.' . (string) (@filemtime($cssPath) ?: 0)
            . '.' . (string) (@filemtime($jsPath) ?: 0);

        $context->hooks()->add(
            HookName::ROUTE_REGISTER,
            static function (Router $router) use ($cssPath, $jsPath, $assetVersion): void {
                $router->get('/plugin/fediverse-share/share.css', static function () use ($cssPath, $assetVersion): void {
                    self::serveAsset($cssPath, 'text/css', $assetVersion);
                });
                $router->get('/plugin/fediverse-share/share.js', static function () use ($jsPath, $assetVersion): void {
                    self::serveAsset($jsPath, 'application/javascript', $assetVersion);
                });
            },
        );

        $context->hooks()->add(
            HookName::THEME_ASSETS,
            static fn (): string => '/plugin/fediverse-share/share.css?v=' . rawurlencode($assetVersion),
        );

        $context->hooks()->add(
            HookName::THEME_SCRIPTS,
            static fn (): string => '/plugin/fediverse-share/share.js?v=' . rawurlencode($assetVersion),
        );

        $context->hooks()->add(
            HookName::TOPIC_ACTIONS,
            static function (Application $hookApp, array $topic, array $board) use ($panel): string {
                unset($board);

                return $panel->render(
                    $hookApp->siteName(),
                    $hookApp->siteUrl(),
                    $topic,
                );
            },
        );
    }

    private static function serveAsset(string $path, string $contentType, string $assetVersion): void
    {
        if (!is_file($path)) {
            Response::notFound();

            return;
        }

        $etag = '"' . hash('sha256', $path . '|' . filemtime($path) . '|' . $assetVersion) . '"';
        http_response_code(200);
        header('Content-Type: ' . $contentType . '; charset=utf-8');
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);

        $ifNoneMatch = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
        if (is_string($ifNoneMatch) && trim($ifNoneMatch) === $etag) {
            http_response_code(304);
            exit;
        }

        readfile($path);
        exit;
    }
}
