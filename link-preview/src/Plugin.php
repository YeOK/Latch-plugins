<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

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
        $cache = new PreviewCache($context->database());
        $http = new HttpTransport($settings->fetchTimeout);
        $resolver = new MetadataResolver($cache, $http, $settings);
        $renderer = new CardRenderer($settings);
        $service = new LinkPreviewService($settings, $resolver, $renderer);

        $pluginPath = $context->path();
        $assetVersion = $app->assetVersion();
        $thumbsDir = $storageRoot . '/plugins/link-preview/thumbs';
        $cssPath = $pluginPath . '/assets/onebox.css';

        $context->hooks()->add(
            HookName::ROUTE_REGISTER,
            static function (Router $router) use ($cssPath, $assetVersion, $cache, $http, $thumbsDir): void {
                $router->get('/plugin/link-preview/onebox.css', static function () use ($cssPath, $assetVersion): void {
                    self::serveAsset($cssPath, 'text/css', $assetVersion);
                });

                $router->get('/plugin/link-preview/image/:hash', static function (array $params) use ($cache, $http, $thumbsDir): void {
                    $hash = (string) ($params['hash'] ?? '');
                    (new ImageHandler($cache, $http, $thumbsDir))->handle($hash);
                });
            },
        );

        $context->hooks()->add(
            HookName::THEME_ASSETS,
            static fn (): string => '/plugin/link-preview/onebox.css?v=' . rawurlencode($assetVersion),
        );

        $context->hooks()->add(
            HookName::POST_FORMAT_AFTER,
            static function (string $html, string $raw): string {
                PreviewLimiter::reset();

                return $html;
            },
            5,
        );

        $context->hooks()->add(
            HookName::POST_FORMAT_LINK,
            static function (string $html, string $url, string $label, bool $standalone) use ($service): string {
                return $service->formatLink($html, $url, $label, $standalone);
            },
            10,
        );

        $context->hooks()->add(
            HookName::CSP_IMG_SRC,
            static fn (): array => ['i.ytimg.com', 'i.vimeocdn.com'],
        );

        if ($settings->embedVideos) {
            $context->hooks()->add(
                HookName::CSP_FRAME_SRC,
                static fn (): array => ['www.youtube-nocookie.com', 'player.vimeo.com'],
            );
        }

        $context->hooks()->add(
            HookName::POST_DELETE,
            static function (array $post) use ($cache): void {
                $body = (string) ($post['body'] ?? '');
                foreach (self::extractUrls($body) as $url) {
                    $cache->delete($url);
                }
            },
            20,
        );

        $cache->purgeExpired();
    }

    /**
     * @return list<string>
     */
    private static function extractUrls(string $body): array
    {
        $urls = [];
        if (preg_match_all('#https://[^\s<>\[\]"\']+#i', $body, $matches)) {
            foreach ($matches[0] as $url) {
                $normalized = SafeUrl::normalize(rtrim($url, '.,);'));
                if ($normalized !== null) {
                    $urls[$normalized] = $normalized;
                }
            }
        }

        return array_values($urls);
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