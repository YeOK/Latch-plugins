<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\LinkPreview;

/**
 * Resets once per post render via post.format.after (priority 5).
 */
final class PreviewLimiter
{
    private static int $count = 0;

    public static function reset(): void
    {
        self::$count = 0;
    }

    public static function canExpand(int $max): bool
    {
        return self::$count < $max;
    }

    public static function expansionCount(): int
    {
        return self::$count;
    }

    public static function recordExpansion(): void
    {
        self::$count++;
    }
}