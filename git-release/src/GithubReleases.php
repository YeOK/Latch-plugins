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
        $body = trim((string) ($data['body'] ?? ''));

        return [
            'tag' => $tag,
            'name' => $name,
            'url' => $htmlUrl,
            'published' => $published,
            'prerelease' => (bool) ($data['prerelease'] ?? false),
            'body_excerpt' => $this->bodyExcerpt($body),
            'repo_url' => 'https://github.com/' . $ownerRepo,
        ];
    }

    private function bodyExcerpt(string $body, int $max = 160): string
    {
        if ($body === '') {
            return '';
        }

        $text = preg_replace('/```[\s\S]*?```/', ' ', $body) ?? $body;
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[#*_>~-]+/', '', $text) ?? $text;
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '') {
            return '';
        }

        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)) . '…';
    }
}