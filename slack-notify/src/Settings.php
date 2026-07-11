<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SlackNotify;

use Latch\Core\Plugins\PluginManifest;
use Latch\Core\Plugins\PluginSettingsStore;

final class Settings
{
    public const EVENT_NEW_TOPIC = 'new_topic';
    public const EVENT_NEW_REPLY = 'new_reply';
    public const EVENT_USER_REGISTERED = 'user_registered';

    /** @var list<string> */
    private const DEFAULT_EVENTS = [self::EVENT_NEW_TOPIC, self::EVENT_NEW_REPLY];

    /**
     * @param list<string> $events
     */
    public function __construct(
        public readonly string $botName,
        public readonly array $events,
        public readonly bool $includeExcerpt,
        public readonly bool $notifyPending,
    ) {
    }

    public static function load(string $storageRoot, PluginManifest $manifest): self
    {
        $store = PluginSettingsStore::forPlugin($manifest, $storageRoot);
        $values = $store->all();

        $botName = trim((string) ($values['bot_name'] ?? 'Latch'));
        if ($botName === '') {
            $botName = 'Latch';
        }

        $events = $values['events'] ?? self::DEFAULT_EVENTS;
        if (!is_array($events) || $events === []) {
            $events = self::DEFAULT_EVENTS;
        }

        $allowed = [
            self::EVENT_NEW_TOPIC,
            self::EVENT_NEW_REPLY,
            self::EVENT_USER_REGISTERED,
        ];
        $events = array_values(array_filter(
            array_map(static fn ($entry): string => is_string($entry) ? trim($entry) : '', $events),
            static fn (string $entry): bool => in_array($entry, $allowed, true),
        ));
        if ($events === []) {
            $events = self::DEFAULT_EVENTS;
        }

        return new self(
            botName: $botName,
            events: $events,
            includeExcerpt: (bool) ($values['include_excerpt'] ?? true),
            notifyPending: (bool) ($values['notify_pending'] ?? false),
        );
    }

    public function wantsEvent(string $event): bool
    {
        return in_array($event, $this->events, true);
    }
}