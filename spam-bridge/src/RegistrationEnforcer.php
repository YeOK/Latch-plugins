<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\SpamBridge;

interface RegistrationEnforcer
{
    public function banSpamRegistration(int $userId, string $provider): void;
}