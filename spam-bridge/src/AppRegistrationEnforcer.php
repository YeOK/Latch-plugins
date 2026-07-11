<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

use Latch\Core\Application;

final class AppRegistrationEnforcer implements RegistrationEnforcer
{
    public function __construct(
        private readonly Application $app,
    ) {
    }

    public function banSpamRegistration(int $userId, string $provider): void
    {
        $this->app->users()->ban($userId, null, 'Automated spam registration (' . $provider . ')');
    }
}