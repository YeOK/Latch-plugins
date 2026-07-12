<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

final class Settings
{
    public function __construct(
        public readonly string $githubRepo,
        public readonly string $heading,
        public readonly int $maxAgeSeconds,
    ) {
    }

    /**
     * @param array<string, mixed> $stored
     */
    public static function fromStored(array $stored): self
    {
        $repo = trim((string) ($stored['github_repo'] ?? 'YeOK/Latch'));
        if ($repo === '') {
            $repo = 'YeOK/Latch';
        }

        $heading = trim((string) ($stored['heading'] ?? 'Latest release'));
        if ($heading === '') {
            $heading = 'Latest release';
        }

        $maxAge = (int) ($stored['max_age_seconds'] ?? 300);
        if ($maxAge < 60) {
            $maxAge = 60;
        }

        if ($maxAge > 3600) {
            $maxAge = 3600;
        }

        return new self($repo, $heading, $maxAge);
    }

    public function isValidRepo(): bool
    {
        return (bool) preg_match('#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $this->githubRepo);
    }
}