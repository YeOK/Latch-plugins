<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

use Latch\Core\Plugins\PluginDatabase;

/**
 * Persists rejections to storage/plugins/spam-bridge/plugin.sqlite.
 */
final class SpamLog
{
    public function __construct(
        private readonly ?PluginDatabase $database,
        private readonly bool $enabled,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function record(
        string $kind,
        string $provider,
        ?int $userId,
        ?int $postId,
        string $reason,
        array $payload = [],
    ): void {
        if (!$this->enabled || $this->database === null) {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO spam_log (created_at, kind, provider, user_id, post_id, reason, payload)
             VALUES (:created_at, :kind, :provider, :user_id, :post_id, :reason, :payload)'
        );
        $stmt->execute([
            'created_at' => gmdate('c'),
            'kind' => $kind,
            'provider' => $provider,
            'user_id' => $userId,
            'post_id' => $postId,
            'reason' => $reason,
            'payload' => $json,
        ]);
    }
}