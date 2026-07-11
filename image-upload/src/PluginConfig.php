<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\ImageUpload;

use Latch\Core\Application;

/**
 * R2 credentials from config/local.php plus upload limits from plugin settings.
 */
final class PluginConfig
{
    /**
     * @param array<string, string> $allowedTypeMap
     */
    public function __construct(
        public readonly string $accountId,
        public readonly string $accessKeyId,
        public readonly string $secretAccessKey,
        public readonly string $bucket,
        public readonly string $publicHost,
        public readonly string $r2Host,
        public readonly int $maxBytes,
        public readonly string $keyPrefix,
        private readonly array $allowedTypeMap,
    ) {
    }

    public static function fromApp(Application $app, Settings $settings): ?self
    {
        $raw = $app->config()->get('plugins.image_upload');
        if (!is_array($raw)) {
            return null;
        }

        $accountId = trim((string) ($raw['account_id'] ?? ''));
        $accessKeyId = trim((string) ($raw['access_key_id'] ?? ''));
        $secret = trim((string) ($raw['secret_access_key'] ?? ''));
        $bucket = trim((string) ($raw['bucket'] ?? ''));
        $publicHost = strtolower(trim((string) ($raw['public_host'] ?? '')));

        if ($accountId === '' || $accessKeyId === '' || $secret === '' || $bucket === '' || $publicHost === '') {
            return null;
        }

        $r2Host = $accountId . '.r2.cloudflarestorage.com';

        return new self(
            accountId: $accountId,
            accessKeyId: $accessKeyId,
            secretAccessKey: $secret,
            bucket: $bucket,
            publicHost: $publicHost,
            r2Host: $r2Host,
            maxBytes: $settings->maxBytes(),
            keyPrefix: $settings->keyPrefix,
            allowedTypeMap: $settings->allowedTypeMap(),
        );
    }

    public function isAllowedContentType(string $contentType): bool
    {
        return isset($this->allowedTypeMap[strtolower(trim($contentType))]);
    }

    public function extensionForContentType(string $contentType): ?string
    {
        return $this->allowedTypeMap[strtolower(trim($contentType))] ?? null;
    }

    public function isAllowedPublicHost(string $host): bool
    {
        return strtolower(trim($host)) === $this->publicHost;
    }

    public function publicUrlForKey(string $objectKey): string
    {
        $segments = explode('/', $objectKey);

        return 'https://' . $this->publicHost . '/' . implode('/', array_map('rawurlencode', $segments));
    }

    public function buildObjectKey(int $userId, string $extension): string
    {
        $uuid = bin2hex(random_bytes(16));

        return $this->keyPrefix . $userId . '/' . $uuid . '.' . $extension;
    }
}