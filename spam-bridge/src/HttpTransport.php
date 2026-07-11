<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

/**
 * Minimal HTTP client for Akismet / Stop Forum Spam (testable via injection).
 */
final class HttpTransport
{
    private const TIMEOUT_SECONDS = 8;

    /**
     * @param ?callable(string $method, string $url, ?string $body): ?string $handler Test hook.
     */
    public function __construct(
        private readonly mixed $handler = null,
    ) {
    }

    /**
     * @param array<string, string> $fields
     */
    public function postForm(string $url, array $fields, array $headers = []): ?string
    {
        $body = http_build_query($fields);
        $headerLines = array_merge(
            ['Content-Type: application/x-www-form-urlencoded'],
            $headers,
        );

        return $this->request('POST', $url, $headerLines, $body);
    }

    public function get(string $url, array $headers = []): ?string
    {
        return $this->request('GET', $url, $headers, null);
    }

    /**
     * @param list<string> $headers
     */
    private function request(string $method, string $url, array $headers, ?string $body): ?string
    {
        if (is_callable($this->handler)) {
            return ($this->handler)($method, $url, $body);
        }

        if (function_exists('curl_init')) {
            return $this->requestViaCurl($method, $url, $headers, $body);
        }

        if ($method === 'GET') {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => self::TIMEOUT_SECONDS,
                    'header' => implode("\r\n", $headers),
                    'ignore_errors' => true,
                ],
            ]);

            $result = @file_get_contents($url, false, $context);

            return is_string($result) ? $result : null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => self::TIMEOUT_SECONDS,
                'header' => implode("\r\n", $headers),
                'content' => $body ?? '',
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return is_string($result) ? $result : null;
    }

    /**
     * @param list<string> $headers
     */
    private function requestViaCurl(string $method, string $url, array $headers, ?string $body): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);

        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return is_string($result) ? $result : null;
    }
}