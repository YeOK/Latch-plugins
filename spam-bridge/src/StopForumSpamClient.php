<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

/**
 * Stop Forum Spam JSON API client.
 */
final class StopForumSpamClient
{
    private const ENDPOINT = 'https://api.stopforumspam.org/api';

    public function __construct(
        private readonly HttpTransport $transport,
    ) {
    }

    /**
     * @param array{ip?: string, email?: string, username?: string} $query
     * @return array{success: bool, matches: list<array{field: string, appears: bool, frequency: int, confidence: float}>}
     */
    public function check(array $query): array
    {
        $params = ['json' => '1', 'confidence' => '1'];
        foreach (['ip', 'email', 'username'] as $field) {
            $value = trim((string) ($query[$field] ?? ''));
            if ($value !== '') {
                $params[$field] = $value;
            }
        }

        if (count($params) <= 2) {
            return ['success' => false, 'matches' => []];
        }

        $url = self::ENDPOINT . '?' . http_build_query($params);
        $response = $this->transport->get($url);
        if ($response === null) {
            return ['success' => false, 'matches' => []];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || (int) ($decoded['success'] ?? 0) !== 1) {
            return ['success' => false, 'matches' => []];
        }

        $matches = [];
        foreach (['ip', 'email', 'username'] as $field) {
            if (!isset($decoded[$field]) || !is_array($decoded[$field])) {
                continue;
            }

            $entry = $decoded[$field];
            $matches[] = [
                'field' => $field,
                'appears' => (int) ($entry['appears'] ?? 0) === 1,
                'frequency' => (int) ($entry['frequency'] ?? 0),
                'confidence' => (float) ($entry['confidence'] ?? 0.0),
            ];
        }

        return ['success' => true, 'matches' => $matches];
    }
}