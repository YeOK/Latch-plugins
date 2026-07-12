<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class HttpTransport
{
    private const USER_AGENT = 'Latch-LinkPreview/1.0 (+https://latch.network)';
    private const MAX_BYTES = 524288;

    /**
     * @param ?callable(string, string, list<string>, ?string): ?string $handler
     */
    public function __construct(
        private readonly int $timeoutSeconds = 5,
        private readonly mixed $handler = null,
    ) {
    }

    public function get(string $url, int $maxBytes = self::MAX_BYTES): ?string
    {
        if (!SafeUrl::normalize($url)) {
            return null;
        }

        if (is_callable($this->handler)) {
            return ($this->handler)('GET', $url, [], null);
        }

        if (function_exists('curl_init')) {
            return $this->getViaCurl($url, $maxBytes);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => 'User-Agent: ' . self::USER_AGENT,
                'ignore_errors' => true,
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

        return strlen($result) > $maxBytes ? substr($result, 0, $maxBytes) : $result;
    }

    private function getViaCurl(string $url, int $maxBytes): ?string
    {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        $buffer = '';
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['User-Agent: ' . self::USER_AGENT],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
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
        curl_close($ch);

        return $buffer !== '' ? $buffer : null;
    }
}