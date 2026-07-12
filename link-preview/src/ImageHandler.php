<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

use Latch\Core\Response;

final class ImageHandler
{
    private const MAX_IMAGE_BYTES = 2097152;

    public function __construct(
        private readonly PreviewCache $cache,
        private readonly HttpTransport $http,
        private readonly string $thumbsDir,
    ) {
    }

    public function handle(string $urlHash): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $urlHash)) {
            Response::notFound();

            return;
        }

        $file = $this->thumbPath($urlHash);
        if (is_file($file)) {
            $this->serveFile($file);

            return;
        }

        $record = $this->cache->getByHash($urlHash);
        if ($record === null || $record->imageUrl === null) {
            Response::notFound();

            return;
        }

        $bytes = $this->http->get($record->imageUrl, self::MAX_IMAGE_BYTES);
        if ($bytes === null || $bytes === '') {
            Response::notFound();

            return;
        }

        if (!is_dir($this->thumbsDir) && !@mkdir($this->thumbsDir, 02770, true)) {
            Response::notFound();

            return;
        }

        $ext = $this->guessExtension($bytes, $record->imageUrl);
        $target = $this->thumbPath($urlHash, $ext);
        if (@file_put_contents($target, $bytes) === false) {
            Response::notFound();

            return;
        }

        @chmod($target, 0640);
        $this->serveFile($target);
    }

    private function thumbPath(string $urlHash, ?string $ext = null): string
    {
        if ($ext !== null) {
            return $this->thumbsDir . '/' . $urlHash . '.' . $ext;
        }

        foreach (['jpg', 'jpeg', 'png', 'webp', 'gif'] as $candidate) {
            $path = $this->thumbsDir . '/' . $urlHash . '.' . $candidate;
            if (is_file($path)) {
                return $path;
            }
        }

        return $this->thumbsDir . '/' . $urlHash . '.jpg';
    }

    private function guessExtension(string $bytes, string $url): string
    {
        if (str_starts_with($bytes, "\x89PNG")) {
            return 'png';
        }
        if (str_starts_with($bytes, 'GIF')) {
            return 'gif';
        }
        if (str_starts_with($bytes, 'RIFF') && str_contains(substr($bytes, 0, 16), 'WEBP')) {
            return 'webp';
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (is_string($path) && preg_match('/\.(jpe?g|png|gif|webp)$/i', $path, $m) === 1) {
            return strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        }

        return 'jpg';
    }

    private function serveFile(string $path): void
    {
        if (!is_file($path)) {
            Response::notFound();

            return;
        }

        $type = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        $etag = '"' . hash('sha256', $path . '|' . filemtime($path)) . '"';
        http_response_code(200);
        header('Content-Type: ' . $type);
        header('Cache-Control: public, max-age=86400');
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