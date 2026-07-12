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
    private const THUMB_WIDTH = 320;
    private const THUMB_HEIGHT = 240;
    private const WEBP_QUALITY = 82;

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

        $cached = $this->findCachedThumb($urlHash);
        if ($cached !== null && str_ends_with($cached, '.webp')) {
            $this->serveFile($cached);

            return;
        }

        if ($cached !== null) {
            @unlink($cached);
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

        $processed = $this->resizeToWebp($bytes);
        if ($processed === null) {
            $ext = $this->guessExtension($bytes, $record->imageUrl);
            $target = $this->thumbPath($urlHash, $ext);
            if (@file_put_contents($target, $bytes) === false) {
                Response::notFound();

                return;
            }

            @chmod($target, 0640);
            $this->serveFile($target);

            return;
        }

        $target = $this->thumbPath($urlHash, 'webp');
        if (@file_put_contents($target, $processed) === false) {
            Response::notFound();

            return;
        }

        @chmod($target, 0640);
        $this->serveFile($target);
    }

    private function findCachedThumb(string $urlHash): ?string
    {
        foreach (['webp', 'jpg', 'jpeg', 'png', 'gif'] as $candidate) {
            $path = $this->thumbPath($urlHash, $candidate);
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function thumbPath(string $urlHash, string $ext): string
    {
        return $this->thumbsDir . '/' . $urlHash . '.' . $ext;
    }

    private function resizeToWebp(string $bytes): ?string
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return null;
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($src);

            return null;
        }

        $maxW = self::THUMB_WIDTH;
        $maxH = self::THUMB_HEIGHT;
        $scale = max($maxW / $srcW, $maxH / $srcH);
        $cropW = max(1, (int) round($maxW / $scale));
        $cropH = max(1, (int) round($maxH / $scale));
        $cropX = (int) round(($srcW - $cropW) / 2);
        $cropY = (int) round(($srcH - $cropH) / 2);

        $dst = imagecreatetruecolor($maxW, $maxH);
        if ($dst === false) {
            imagedestroy($src);

            return null;
        }

        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $maxW, $maxH, $cropW, $cropH);
        imagedestroy($src);

        ob_start();
        $ok = imagewebp($dst, null, self::WEBP_QUALITY);
        imagedestroy($dst);
        if (!$ok) {
            ob_end_clean();

            return null;
        }

        $out = ob_get_clean();

        return is_string($out) && $out !== '' ? $out : null;
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