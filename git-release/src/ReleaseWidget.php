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
    public function __construct(private readonly GithubReleases $github = new GithubReleases())
    {
    }

    public function renderHtml(Settings $settings): string
    {
        if (!$settings->isValidRepo()) {
            return '';
        }

        $release = $this->github->latestRelease($settings->githubRepo);
        if ($release === null) {
            return '';
        }

        $heading = htmlspecialchars($settings->heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $name = htmlspecialchars($release['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $tag = htmlspecialchars($release['tag'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $url = htmlspecialchars($release['url'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $published = $this->formatPublished($release['published']);

        $meta = $published !== ''
            ? '<p class="muted latch-git-release-meta">' . htmlspecialchars($published, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            : '';

        return <<<HTML
<section class="latch-git-release" aria-label="{$heading}">
    <h2 class="latch-git-release-title">{$heading}</h2>
    <p class="latch-git-release-name"><strong>{$name}</strong> <span class="muted">{$tag}</span></p>
    {$meta}
    <p class="latch-git-release-actions">
        <a class="btn btn-primary" href="{$url}" rel="noopener noreferrer" target="_blank">View release</a>
    </p>
</section>
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

        return gmdate('Y-m-d', $timestamp);
    }
}