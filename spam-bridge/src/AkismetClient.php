<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

/**
 * Akismet REST comment-check client.
 */
final class AkismetClient
{
    private const ENDPOINT = 'https://rest.akismet.com/1.1/comment-check';

    public function __construct(
        private readonly string $apiKey,
        private readonly HttpTransport $transport,
    ) {
    }

    /**
     * @param array<string, string> $fields
     * @return array{spam: bool, error: ?string, pro_tip: ?string}
     */
    public function commentCheck(array $fields): array
    {
        $payload = array_merge(['api_key' => $this->apiKey], $fields);
        $response = $this->transport->postForm(self::ENDPOINT, $payload, [
            'User-Agent: Latch/1.0 | SpamBridge/1.0',
        ]);

        if ($response === null) {
            return ['spam' => false, 'error' => 'request_failed', 'pro_tip' => null];
        }

        $body = trim($response);
        if ($body === 'true') {
            return ['spam' => true, 'error' => null, 'pro_tip' => null];
        }

        if ($body === 'false') {
            return ['spam' => false, 'error' => null, 'pro_tip' => null];
        }

        return ['spam' => false, 'error' => $body !== '' ? $body : 'invalid_response', 'pro_tip' => null];
    }
}