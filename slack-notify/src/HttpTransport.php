<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

/**
 * Minimal HTTP client for incoming webhooks (testable via injection).
 */
final class HttpTransport
{
    private const TIMEOUT_SECONDS = 5;

    /**
     * @param ?callable(string $method, string $url, ?string $body, array $headers): ?string $handler
     */
    public function __construct(
        private readonly mixed $handler = null,
    ) {
    }

    /**
     * @param list<string> $headers
     */
    public function postJson(string $url, string $json, array $headers = []): ?string
    {
        $headerLines = array_merge(['Content-Type: application/json'], $headers);

        return $this->request('POST', $url, $headerLines, $json);
    }

    /**
     * @param list<string> $headers
     */
    private function request(string $method, string $url, array $headers, ?string $body): ?string
    {
        if (is_callable($this->handler)) {
            return ($this->handler)($method, $url, $body, $headers);
        }

        if (function_exists('curl_init')) {
            return $this->requestViaCurl($method, $url, $headers, $body);
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
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

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return is_string($result) ? $result : null;
    }
}