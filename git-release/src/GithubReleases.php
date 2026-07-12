<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

final class GithubReleases
{
    public function __construct(private readonly HttpTransport $http = new HttpTransport())
    {
    }

    /**
     * @return array{tag: string, name: string, url: string, published: string}|null
     */
    public function latestRelease(string $ownerRepo): ?array
    {
        if (!preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $ownerRepo)) {
            return null;
        }

        $url = 'https://api.github.com/repos/' . $ownerRepo . '/releases/latest';
        $raw = $this->http->get($url);
        if ($raw === null) {
            return null;
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        $tag = trim((string) ($data['tag_name'] ?? ''));
        $htmlUrl = trim((string) ($data['html_url'] ?? ''));
        if ($tag === '' || $htmlUrl === '' || !preg_match('#^https://github\.com/#', $htmlUrl)) {
            return null;
        }

        $name = trim((string) ($data['name'] ?? $tag));
        if ($name === '') {
            $name = $tag;
        }

        $published = trim((string) ($data['published_at'] ?? ''));

        return [
            'tag' => $tag,
            'name' => $name,
            'url' => $htmlUrl,
            'published' => $published,
        ];
    }
}