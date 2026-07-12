<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

final class MetadataResolver
{
    public function __construct(
        private readonly PreviewCache $cache,
        private readonly HttpTransport $http,
        private readonly Settings $settings,
    ) {
    }

    public function resolve(string $url): ?PreviewRecord
    {
        $url = SafeUrl::normalize($url);
        if ($url === null) {
            return null;
        }

        $cached = $this->cache->get($url);
        if ($cached !== null) {
            return $cached;
        }

        $record = $this->buildRecord($url);
        if ($record !== null) {
            $this->cache->put($record, $this->settings->cacheTtlHours);
        }

        return $record;
    }

    private function buildRecord(string $url): ?PreviewRecord
    {
        $hash = PreviewCache::hash($url);
        $video = VideoUrl::parse($url);

        if ($video !== null) {
            return $this->buildVideoRecord($url, $hash, $video);
        }

        return $this->buildGenericRecord($url, $hash);
    }

    /**
     * @param array{kind: string, id: string} $video
     */
    private function buildVideoRecord(string $url, string $hash, array $video): PreviewRecord
    {
        if ($video['kind'] === VideoUrl::KIND_YOUTUBE) {
            return new PreviewRecord(
                url: $url,
                urlHash: $hash,
                kind: VideoUrl::KIND_YOUTUBE,
                title: 'YouTube video',
                description: null,
                imageUrl: 'https://i.ytimg.com/vi/' . $video['id'] . '/hqdefault.jpg',
                siteName: 'YouTube',
                videoId: $video['id'],
            );
        }

        return new PreviewRecord(
            url: $url,
            urlHash: $hash,
            kind: VideoUrl::KIND_VIMEO,
            title: 'Vimeo video',
            description: null,
            imageUrl: null,
            siteName: 'Vimeo',
            videoId: $video['id'],
        );
    }

    private function buildGenericRecord(string $url, string $hash): ?PreviewRecord
    {
        $html = $this->http->get($url);
        if ($html === null) {
            return new PreviewRecord(
                url: $url,
                urlHash: $hash,
                kind: 'generic',
                title: null,
                description: null,
                imageUrl: null,
                siteName: SafeUrl::host($url),
            );
        }

        $og = OgParser::parse($html, $url);

        return new PreviewRecord(
            url: $url,
            urlHash: $hash,
            kind: 'generic',
            title: $og['title'],
            description: $og['description'],
            imageUrl: $og['image_url'],
            siteName: $og['site_name'] ?? SafeUrl::host($url),
        );
    }
}