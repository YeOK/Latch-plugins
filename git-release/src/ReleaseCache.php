<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

final class ReleaseCache
{
    public function __construct(private readonly string $directory)
    {
    }

    /**
     * @return array{
     *     tag: string,
     *     name: string,
     *     url: string,
     *     published: string,
     *     prerelease: bool,
     *     body_excerpt: string,
     *     repo_url: string
     * }|null
     */
    public function getFresh(string $ownerRepo, int $maxAgeSeconds): ?array
    {
        $entry = $this->read($ownerRepo);
        if ($entry === null) {
            return null;
        }

        if (time() - $entry['fetched_at'] > $maxAgeSeconds) {
            return null;
        }

        return $entry['release'];
    }

    /**
     * @return array{
     *     tag: string,
     *     name: string,
     *     url: string,
     *     published: string,
     *     prerelease: bool,
     *     body_excerpt: string,
     *     repo_url: string
     * }|null
     */
    public function getStale(string $ownerRepo): ?array
    {
        $entry = $this->read($ownerRepo);

        return $entry['release'] ?? null;
    }

    /**
     * @param array{
     *     tag: string,
     *     name: string,
     *     url: string,
     *     published: string,
     *     prerelease: bool,
     *     body_excerpt: string,
     *     repo_url: string
     * } $release
     */
    public function put(string $ownerRepo, array $release): void
    {
        if (!$this->ensureDirectory()) {
            return;
        }

        $payload = json_encode([
            'fetched_at' => time(),
            'release' => $release,
        ], JSON_THROW_ON_ERROR);

        @file_put_contents($this->pathFor($ownerRepo), $payload, LOCK_EX);
    }

    public function purge(string $ownerRepo): bool
    {
        $path = $this->pathFor($ownerRepo);
        if (!is_file($path)) {
            return false;
        }

        return @unlink($path);
    }

    public function purgeAll(): int
    {
        if (!is_dir($this->directory)) {
            return 0;
        }

        $removed = 0;
        foreach (glob(rtrim($this->directory, '/') . '/*.json') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $removed++;
            }
        }

        return $removed;
    }

    public function entryCount(): int
    {
        if (!is_dir($this->directory)) {
            return 0;
        }

        return count(glob(rtrim($this->directory, '/') . '/*.json') ?: []);
    }

    /**
     * @return array{fetched_at: int, release: array<string, mixed>}|null
     */
    private function read(string $ownerRepo): ?array
    {
        $path = $this->pathFor($ownerRepo);
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!isset($decoded['fetched_at'], $decoded['release']) || !is_array($decoded['release'])) {
            return null;
        }

        $release = $this->normalizeRelease($decoded['release']);
        if ($release === null) {
            return null;
        }

        return [
            'fetched_at' => (int) $decoded['fetched_at'],
            'release' => $release,
        ];
    }

    /**
     * @param array<string, mixed> $release
     * @return array{
     *     tag: string,
     *     name: string,
     *     url: string,
     *     published: string,
     *     prerelease: bool,
     *     body_excerpt: string,
     *     repo_url: string
     * }|null
     */
    private function normalizeRelease(array $release): ?array
    {
        $tag = trim((string) ($release['tag'] ?? ''));
        $url = trim((string) ($release['url'] ?? ''));
        if ($tag === '' || $url === '') {
            return null;
        }

        return [
            'tag' => $tag,
            'name' => trim((string) ($release['name'] ?? $tag)),
            'url' => $url,
            'published' => trim((string) ($release['published'] ?? '')),
            'prerelease' => (bool) ($release['prerelease'] ?? false),
            'body_excerpt' => trim((string) ($release['body_excerpt'] ?? '')),
            'repo_url' => trim((string) ($release['repo_url'] ?? '')),
        ];
    }

    private function pathFor(string $ownerRepo): string
    {
        return rtrim($this->directory, '/') . '/' . hash('sha256', strtolower($ownerRepo)) . '.json';
    }

    private function ensureDirectory(): bool
    {
        if (is_dir($this->directory)) {
            return is_writable($this->directory);
        }

        return @mkdir($this->directory, 0775, true) || is_dir($this->directory);
    }
}