<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

use Latch\Core\Plugins\PluginDatabase;

final class PreviewCache
{
    public function __construct(
        private readonly ?PluginDatabase $database,
    ) {
    }

    public static function hash(string $url): string
    {
        return hash('sha256', $url);
    }

    public function get(string $url): ?PreviewRecord
    {
        return $this->getByHash(self::hash($url), $url);
    }

    public function getByHash(string $hash, ?string $urlForDelete = null): ?PreviewRecord
    {
        if ($this->database === null) {
            return null;
        }

        $stmt = $this->database->pdo()->prepare(
            'SELECT url_hash, url, kind, title, description, image_url, site_name, extra_json, expires_at
             FROM preview_cache WHERE url_hash = :hash LIMIT 1',
        );
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        if ((string) ($row['expires_at'] ?? '') < gmdate('Y-m-d H:i:s')) {
            $deleteUrl = $urlForDelete ?? (string) $row['url'];
            $this->delete($deleteUrl);

            return null;
        }

        return $this->rowToRecord($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function rowToRecord(array $row): PreviewRecord
    {
        $videoId = null;
        $extra = json_decode((string) ($row['extra_json'] ?? ''), true);
        if (is_array($extra) && isset($extra['video_id'])) {
            $videoId = (string) $extra['video_id'];
        }

        return new PreviewRecord(
            url: (string) $row['url'],
            urlHash: (string) $row['url_hash'],
            kind: (string) $row['kind'],
            title: $row['title'] !== null ? (string) $row['title'] : null,
            description: $row['description'] !== null ? (string) $row['description'] : null,
            imageUrl: $row['image_url'] !== null ? (string) $row['image_url'] : null,
            siteName: $row['site_name'] !== null ? (string) $row['site_name'] : null,
            videoId: $videoId,
        );
    }

    public function put(PreviewRecord $record, int $ttlHours): void
    {
        if ($this->database === null) {
            return;
        }

        $now = gmdate('Y-m-d H:i:s');
        $expires = gmdate('Y-m-d H:i:s', time() + ($ttlHours * 3600));
        $extra = $record->videoId !== null
            ? json_encode(['video_id' => $record->videoId], JSON_THROW_ON_ERROR)
            : null;

        $stmt = $this->database->pdo()->prepare(
            'INSERT INTO preview_cache (url_hash, url, kind, title, description, image_url, site_name, extra_json, fetched_at, expires_at)
             VALUES (:hash, :url, :kind, :title, :description, :image, :site, :extra, :fetched, :expires)
             ON CONFLICT(url_hash) DO UPDATE SET
               kind = excluded.kind,
               title = excluded.title,
               description = excluded.description,
               image_url = excluded.image_url,
               site_name = excluded.site_name,
               extra_json = excluded.extra_json,
               fetched_at = excluded.fetched_at,
               expires_at = excluded.expires_at',
        );
        $stmt->execute([
            'hash' => $record->urlHash,
            'url' => $record->url,
            'kind' => $record->kind,
            'title' => $record->title,
            'description' => $record->description,
            'image' => $record->imageUrl,
            'site' => $record->siteName,
            'extra' => $extra,
            'fetched' => $now,
            'expires' => $expires,
        ]);
    }

    public function delete(string $url): void
    {
        if ($this->database === null) {
            return;
        }

        $stmt = $this->database->pdo()->prepare('DELETE FROM preview_cache WHERE url_hash = :hash');
        $stmt->execute(['hash' => self::hash($url)]);
    }

    public function purgeExpired(): void
    {
        if ($this->database === null) {
            return;
        }

        $stmt = $this->database->pdo()->prepare('DELETE FROM preview_cache WHERE expires_at < :now');
        $stmt->execute(['now' => gmdate('Y-m-d H:i:s')]);
    }
}