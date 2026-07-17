<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\BoardIconPack;

use Latch\Core\BoardIcons\BoardIconRegistry;

/**
 * Loads SVG board icons from assets/icons and registers them with optional keywords.
 */
final class IconPack
{
    /**
     * Keyword hints for auto-suggest when creating boards (Latch 0.4.7+ registerKeywords).
     *
     * @var array<string, list<string>>
     */
    private const KEYWORDS = [
        'books' => ['books', 'docs', 'documentation', 'wiki', 'library', 'reading', 'manual'],
        'server' => ['server', 'hosting', 'infrastructure', 'ops', 'deploy', 'sysadmin', 'vps'],
        'security' => ['security', 'privacy', 'lock', 'hardening', 'auth', '2fa', 'secrets'],
        'open-source' => ['open-source', 'opensource', 'oss', 'foss', 'github', 'gitlab', 'source'],
        'rocket' => ['rocket', 'launch', 'release', 'shipping', 'changelog', 'ship'],
        'globe' => ['globe', 'world', 'international', 'i18n', 'locale', 'global', 'language'],
        'calendar' => ['calendar', 'events', 'event', 'meetup', 'schedule', 'agenda'],
        'bug' => ['bug', 'bugs', 'issue', 'issues', 'tracker', 'report', 'defect'],
        'terminal' => ['terminal', 'cli', 'shell', 'console', 'command', 'ssh'],
        'database' => ['database', 'data', 'sql', 'sqlite', 'storage', 'db'],
        'film' => ['film', 'video', 'videos', 'movies', 'media', 'stream'],
        'camera' => ['camera', 'photo', 'photos', 'photography', 'gallery', 'images'],
        'briefcase' => ['briefcase', 'jobs', 'careers', 'business', 'work', 'hiring'],
        'heart' => ['heart', 'community', 'thanks', 'kudos', 'love', 'appreciation'],
        'tools' => ['tools', 'utilities', 'utility', 'settings', 'config', 'workshop'],
        'chat' => ['chat', 'messages', 'messaging', 'conversation', 'dm', 'inbox'],
    ];

    public function __construct(
        private readonly string $pluginDir,
    ) {
    }

    public function register(BoardIconRegistry $registry): int
    {
        $dir = $this->pluginDir . '/assets/icons';
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        foreach (glob($dir . '/*.svg') ?: [] as $path) {
            $key = strtolower(pathinfo($path, PATHINFO_FILENAME));
            $key = preg_replace('/[^a-z0-9]+/', '-', $key) ?? '';
            $key = trim($key, '-');
            if ($key === '') {
                continue;
            }

            $svg = trim((string) file_get_contents($path));
            try {
                $registry->register($key, $svg);
            } catch (\Throwable) {
                continue;
            }

            $keywords = self::KEYWORDS[$key] ?? [$key];
            if (method_exists($registry, 'registerKeywords')) {
                try {
                    $registry->registerKeywords($key, $keywords);
                } catch (\Throwable) {
                    // Older cores without keyword registration still get icons.
                }
            }

            $count++;
        }

        return $count;
    }
}
