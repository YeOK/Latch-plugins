<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\GitRelease;

final class ReleaseWidget
{
    public function __construct(
        private readonly GithubReleases $github = new GithubReleases(),
    ) {
    }

    public function renderHtml(Settings $settings): string
    {
        if (!$settings->isValidRepo()) {
            return '';
        }

        $release = $this->github->latestRelease($settings->githubRepo, $settings->maxAgeSeconds);
        if ($release === null) {
            return '';
        }

        $heading = $this->escape($settings->heading);
        $name = $this->escape($release['name']);
        $tag = $this->escape($release['tag']);
        $url = $this->escape($release['url']);
        $repoUrl = $this->escape($release['repo_url']);
        $repoLabel = $this->escape($settings->githubRepo);
        $published = $this->formatPublished($release['published']);
        $publishedHtml = $published !== ''
            ? '<time class="latch-git-release__date" datetime="' . $this->escape($release['published']) . '">' . $this->escape($published) . '</time>'
            : '';

        $prereleaseBadge = $release['prerelease']
            ? '<span class="latch-git-release__badge latch-git-release__badge--pre">Pre-release</span>'
            : '<span class="latch-git-release__badge latch-git-release__badge--stable">Stable</span>';

        $excerpt = $release['body_excerpt'];
        $excerptHtml = $excerpt !== ''
            ? '<p class="latch-git-release__excerpt">' . $this->escape($excerpt) . '</p>'
            : '';

        $brandIcon = $this->latchMarkSvg();
        $arrowIcon = $this->arrowIconSvg();

        return <<<HTML
<article class="latch-git-release" aria-label="{$heading}">
    <div class="latch-git-release__accent" aria-hidden="true"></div>
    <div class="latch-git-release__inner">
        <div class="latch-git-release__brand" aria-hidden="true">
            <span class="latch-git-release__icon">{$brandIcon}</span>
        </div>
        <div class="latch-git-release__content">
            <header class="latch-git-release__header">
                <p class="latch-git-release__eyebrow">GitHub release</p>
                <h2 class="latch-git-release__heading">{$heading}</h2>
            </header>
            <div class="latch-git-release__release">
                <div class="latch-git-release__title-row">
                    <h3 class="latch-git-release__title">{$name}</h3>
                    <code class="latch-git-release__tag">{$tag}</code>
                </div>
                <div class="latch-git-release__meta">
                    {$prereleaseBadge}
                    {$publishedHtml}
                    <a class="latch-git-release__repo" href="{$repoUrl}" rel="noopener noreferrer" target="_blank">{$repoLabel}</a>
                </div>
                {$excerptHtml}
            </div>
            <footer class="latch-git-release__actions latch-git-release-actions">
                <a class="btn btn-primary latch-git-release__btn latch-git-release__btn--primary" href="{$url}" rel="noopener noreferrer" target="_blank">
                    <span>View release</span>
                    {$arrowIcon}
                </a>
                <a class="btn latch-git-release__btn latch-git-release__btn--secondary" href="{$repoUrl}" rel="noopener noreferrer" target="_blank">
                    <span>Repository</span>
                </a>
            </footer>
        </div>
    </div>
</article>
HTML;
    }

    private function formatPublished(string $iso): string
    {
        if ($iso === '') {
            return '';
        }

        $timestamp = strtotime($iso);
        if ($timestamp === false) {
            return '';
        }

        return gmdate('M j, Y', $timestamp);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function latchMarkSvg(): string
    {
        return <<<'SVG'
<svg class="latch-git-release__icon-svg latch-git-release__icon-svg--app" width="52" height="52" viewBox="0 0 32 32" aria-hidden="true" focusable="false">
    <rect width="32" height="32" rx="7" fill="var(--accent, #2f6fed)" />
    <g fill="#ffffff" transform="translate(16 16) scale(0.5) translate(-46 -40)">
        <path d="M35 15 C24 15 15 24 15 35 C15 46 24 55 35 55 L50 55 L50 43 L35 43 C31 43 27 39 27 35 C27 31 31 27 35 27 L50 27 L50 15 Z" />
        <path d="M42 25 L42 37 L57 37 C61 37 65 41 65 45 C65 49 61 53 57 53 L42 53 L42 65 L57 65 C68 65 77 56 77 45 C77 34 68 25 57 25 Z" />
    </g>
</svg>
SVG;
    }

    private function arrowIconSvg(): string
    {
        return <<<'SVG'
<svg class="latch-git-release__btn-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M7 17L17 7M17 7H9M17 7v8"/>
</svg>
SVG;
    }
}