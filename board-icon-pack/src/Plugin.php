<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\BoardIconPack;

use Latch\Core\BoardIcons\BoardIconRegistry;
use Latch\Core\Plugins\HookName;
use Latch\Core\Plugins\PluginContext;
use Latch\Core\Plugins\PluginInterface;

final class Plugin implements PluginInterface
{
    public function register(PluginContext $context): void
    {
        $pack = new IconPack($context->path());

        $context->hooks()->add(
            HookName::BOARD_ICONS,
            static function (BoardIconRegistry $registry) use ($pack): void {
                $pack->register($registry);
            },
        );
    }
}
