<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\ForumStats;

final class StatsPanel
{
    public function __construct(
        private readonly string $assetVersion = '',
        private readonly string $pluginVersion = '',
    ) {
    }

    public function render(int $posts, int $topics, int $members): string
    {
        $postsLabel = $this->formatInteger($posts);
        $topicsLabel = $this->formatInteger($topics);
        $membersLabel = $this->formatInteger($members);

        $chartIcon = $this->chartIconSvg();
        $postsIcon = $this->postsIconSvg();
        $topicsIcon = $this->topicsIconSvg();
        $membersIcon = $this->membersIconSvg();
        $stylesheet = $this->stylesheetTag();

        return <<<HTML
{$stylesheet}<article class="latch-forum-stats" aria-label="Forum statistics">
    <div class="latch-forum-stats__accent" aria-hidden="true"></div>
    <div class="latch-forum-stats__inner">
        <div class="latch-forum-stats__brand" aria-hidden="true">
            <span class="latch-forum-stats__icon">{$chartIcon}</span>
        </div>
        <div class="latch-forum-stats__content">
            <header class="latch-forum-stats__header">
                <p class="latch-forum-stats__eyebrow">Community</p>
                <h2 class="latch-forum-stats__heading">Forum at a glance</h2>
            </header>
            <div class="latch-forum-stats__grid">
                <div class="latch-forum-stats__stat">
                    <span class="latch-forum-stats__stat-icon" aria-hidden="true">{$postsIcon}</span>
                    <span class="latch-forum-stats__stat-value">{$postsLabel}</span>
                    <span class="latch-forum-stats__stat-label">Posts</span>
                </div>
                <div class="latch-forum-stats__stat">
                    <span class="latch-forum-stats__stat-icon" aria-hidden="true">{$topicsIcon}</span>
                    <span class="latch-forum-stats__stat-value">{$topicsLabel}</span>
                    <span class="latch-forum-stats__stat-label">Topics</span>
                </div>
                <div class="latch-forum-stats__stat">
                    <span class="latch-forum-stats__stat-icon" aria-hidden="true">{$membersIcon}</span>
                    <span class="latch-forum-stats__stat-value">{$membersLabel}</span>
                    <span class="latch-forum-stats__stat-label">Registered members</span>
                </div>
            </div>
        </div>
    </div>
</article>
HTML;
    }

    private function formatInteger(int $value): string
    {
        return number_format($value);
    }

    private function stylesheetTag(): string
    {
        $parts = array_values(array_filter([$this->pluginVersion, $this->assetVersion], static fn (string $v): bool => $v !== ''));
        $query = $parts !== [] ? '?v=' . rawurlencode(implode('.', $parts)) : '';

        return '<link rel="stylesheet" href="/plugin/forum-stats/stats.css' . $query . '">';
    }

    private function chartIconSvg(): string
    {
        return <<<'SVG'
<svg class="latch-forum-stats__icon-svg" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M4 19V5M10 19V9M16 19v-6M22 19V3"/>
</svg>
SVG;
    }

    private function postsIconSvg(): string
    {
        return <<<'SVG'
<svg class="latch-forum-stats__stat-icon-svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
</svg>
SVG;
    }

    private function topicsIconSvg(): string
    {
        return <<<'SVG'
<svg class="latch-forum-stats__stat-icon-svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
</svg>
SVG;
    }

    private function membersIconSvg(): string
    {
        return <<<'SVG'
<svg class="latch-forum-stats__stat-icon-svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
</svg>
SVG;
    }
}