<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

use Latch\Core\Application;
use Latch\Core\Response;

final class CachePurgeHandler
{
    public function __construct(
        private readonly Application $app,
        private readonly string $cacheDir,
    ) {
    }

    public function handle(): void
    {
        $this->app->auth()->requireAdmin();

        if (!$this->app->csrf()->validate($this->app->request()->input('_csrf'))) {
            $this->app->session()->flash('error', 'Invalid form token.');
            Response::redirect('/admin/plugins/git-release/settings');
        }

        $removed = (new ReleaseCache($this->cacheDir))->purgeAll();

        $this->app->session()->flash(
            'success',
            $removed > 0
                ? "Purged {$removed} cached release" . ($removed === 1 ? '' : 's') . '.'
                : 'Release cache was already empty.',
        );

        Response::redirect('/admin/plugins/git-release/settings');
    }
}