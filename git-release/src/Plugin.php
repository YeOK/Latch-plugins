<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

use Latch\Core\Application;
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
        $app = $context->app();
        $assetVersion = $app->assetVersion();
        $pluginVersion = $context->manifest()->version;
        $storageRoot = (string) $app->config()->get('paths.storage');
        $settingsStore = PluginSettingsStore::forPlugin($context->manifest(), $storageRoot);

        $cacheDir = rtrim($storageRoot, '/') . '/plugins/git-release/cache';

        $context->hooks()->add(
            HookName::ROUTE_REGISTER,
            static function (Router $router, Application $hookApp) use (
                $pluginPath,
                $settingsStore,
                $assetVersion,
                $pluginVersion,
                $cacheDir,
            ): void {
                $router->get('/plugin/git-release/widget.json', static function () use ($settingsStore, $assetVersion, $pluginVersion, $cacheDir): void {
                    $settings = Settings::fromStored($settingsStore->all());
                    $github = new GithubReleases(cache: new ReleaseCache($cacheDir));
                    $html = (new ReleaseWidget($assetVersion, $pluginVersion, $github))->renderHtml($settings);
                    Response::json(['html' => $html], 200, $settings->maxAgeSeconds);
                });

                $cssPath = $pluginPath . '/assets/widget.css';
                $router->get('/plugin/git-release/widget.css', static function () use ($cssPath, $assetVersion): void {
                    self::serveAsset($cssPath, 'text/css', $assetVersion);
                });

                $jsPath = $pluginPath . '/assets/admin-tools.js';
                $router->get('/plugin/git-release/admin-tools.js', static function () use ($jsPath, $assetVersion): void {
                    self::serveAsset($jsPath, 'application/javascript', $assetVersion);
                });

                $router->post('/admin/plugins/git-release/purge-cache', static function () use ($hookApp, $cacheDir): void {
                    (new CachePurgeHandler($hookApp, $cacheDir))->handle();
                });
            },
        );

        $context->hooks()->add(
            HookName::THEME_SCRIPTS,
            static function (Application $hookApp) use ($assetVersion): string {
                if (!$hookApp->auth()->isAdmin()) {
                    return '';
                }

                if ($hookApp->request()->path() !== '/admin/plugins/git-release/settings') {
                    return '';
                }

                return '/plugin/git-release/admin-tools.js?v=' . rawurlencode($assetVersion);
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