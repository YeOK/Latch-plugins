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

        $githubIcon = $this->githubMarkSvg();
        $arrowIcon = $this->arrowIconSvg();

        return <<<HTML
<article class="latch-git-release" aria-label="{$heading}">
    <div class="latch-git-release__accent" aria-hidden="true"></div>
    <div class="latch-git-release__inner">
        <div class="latch-git-release__brand" aria-hidden="true">
            <span class="latch-git-release__icon">{$githubIcon}</span>
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

    private function githubMarkSvg(): string
    {
        return <<<'SVG'
<svg class="latch-git-release__icon-svg" width="28" height="28" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
    <path fill="currentColor" d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.438 9.794 8.205 11.387.6.11.82-.26.82-.577 0-.285-.01-1.04-.016-2.04-3.338.726-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.09-.745.083-.73.083-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.834 2.807 1.304 3.492.997.108-.775.418-1.305.762-1.605-2.665-.303-5.466-1.332-5.466-5.93 0-1.31.468-2.38 1.236-3.22-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23a11.5 11.5 0 0 1 3.003-.404c1.02.005 2.047.138 3.003.404 2.291-1.552 3.297-1.23 3.297-1.23.654 1.652.243 2.873.12 3.176.77.84 1.234 1.91 1.234 3.22 0 4.61-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222 0 1.606-.015 2.898-.015 3.293 0 .32.216.694.825.576C20.565 21.792 24 17.297 24 12 24 5.37 18.63 0 12 0z"/>
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