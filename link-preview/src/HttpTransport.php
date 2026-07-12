<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

use Latch\Support\OutboundUrlGuard;

final class HttpTransport
{
    private const USER_AGENT = 'Latch-LinkPreview/1.0 (+https://latch.network)';
    private const MAX_BYTES = 524288;
    private const MAX_REDIRECTS = 3;

    /**
     * @param ?callable(string, string, list<string>, ?string): (string|array{status: int, headers?: array<string, string>, body?: string}|null) $handler
     */
    public function __construct(
        private readonly int $timeoutSeconds = 5,
        private readonly mixed $handler = null,
    ) {
    }

    public function get(string $url, int $maxBytes = self::MAX_BYTES): ?string
    {
        $currentUrl = trim($url);

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            $safeUrl = OutboundUrlGuard::normalizePublicHttpsUrl($currentUrl);
            if ($safeUrl === null) {
                return null;
            }

            $currentUrl = $safeUrl;
            $response = $this->request($currentUrl, $maxBytes);
            if ($response === null) {
                return null;
            }

            $status = $response['status'];
            if ($status >= 300 && $status < 400) {
                $location = $response['location'] ?? null;
                if (!is_string($location) || $location === '') {
                    return null;
                }

                $nextUrl = OutboundUrlGuard::resolveRedirectLocation($currentUrl, $location);
                if ($nextUrl === null) {
                    return null;
                }

                $currentUrl = $nextUrl;
                continue;
            }

            if ($status >= 200 && $status < 300) {
                return $response['body'] !== '' ? $response['body'] : null;
            }

            return null;
        }

        return null;
    }

    /**
     * @return array{status: int, location: ?string, body: string}|null
     */
    private function request(string $url, int $maxBytes): ?array
    {
        if (is_callable($this->handler)) {
            $result = ($this->handler)('GET', $url, [], null);
            if ($result === null) {
                return null;
            }

            if (is_array($result)) {
                $headers = $result['headers'] ?? [];
                $location = null;
                if (isset($headers['Location']) && is_string($headers['Location'])) {
                    $location = $headers['Location'];
                } elseif (isset($headers['location']) && is_string($headers['location'])) {
                    $location = $headers['location'];
                }

                return [
                    'status' => (int) ($result['status'] ?? 200),
                    'location' => $location,
                    'body' => (string) ($result['body'] ?? ''),
                ];
            }

            return [
                'status' => 200,
                'location' => null,
                'body' => $result,
            ];
        }

        if (function_exists('curl_init')) {
            return $this->requestViaCurl($url, $maxBytes);
        }

        return $this->requestViaStream($url, $maxBytes);
    }

    /**
     * @return array{status: int, location: ?string, body: string}|null
     */
    private function requestViaCurl(string $url, int $maxBytes): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        $buffer = '';
        $status = 0;
        $location = null;

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['User-Agent: ' . self::USER_AGENT],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$buffer, $maxBytes): int {
                $remaining = $maxBytes - strlen($buffer);
                if ($remaining <= 0) {
                    return 0;
                }
                $buffer .= substr($chunk, 0, $remaining);

                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($status === 0) {
            return null;
        }

        [$headerBlock, $body] = self::splitRawResponse($buffer);
        $location = self::headerValue($headerBlock, 'Location');

        return [
            'status' => $status,
            'location' => $location,
            'body' => $body,
        ];
    }

    /**
     * @return array{status: int, location: ?string, body: string}|null
     */
    private function requestViaStream(string $url, int $maxBytes): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => 'User-Agent: ' . self::USER_AGENT,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        if (!is_string($result)) {
            return null;
        }

        $body = strlen($result) > $maxBytes ? substr($result, 0, $maxBytes) : $result;
        $status = self::statusFromHeaders($http_response_header ?? []);
        $location = self::headerValue(implode("\r\n", $http_response_header ?? []), 'Location');

        return [
            'status' => $status,
            'location' => $location,
            'body' => $body,
        ];
    }

    /**
     * @param list<string> $headers
     */
    private static function statusFromHeaders(array $headers): int
    {
        $status = 0;
        foreach ($headers as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $line, $match) === 1) {
                $status = (int) $match[1];
            }
        }

        return $status;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitRawResponse(string $raw): array
    {
        $separator = "\r\n\r\n";
        $pos = strpos($raw, $separator);
        if ($pos === false) {
            $separator = "\n\n";
            $pos = strpos($raw, $separator);
        }

        if ($pos === false) {
            return ['', $raw];
        }

        $headerBlock = substr($raw, 0, $pos);
        $body = substr($raw, $pos + strlen($separator));

        return [$headerBlock, $body];
    }

    private static function headerValue(string $headerBlock, string $name): ?string
    {
        if ($headerBlock === '') {
            return null;
        }

        $pattern = '/^' . preg_quote($name, '/') . ':\s*(.+)$/im';
        if (preg_match($pattern, $headerBlock, $match) !== 1) {
            return null;
        }

        return trim($match[1]);
    }
}