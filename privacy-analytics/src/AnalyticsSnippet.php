<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 Latch contributors
 *
 * SPDX-License-Identifier: MIT
 */


namespace Latch\Plugins\PrivacyAnalytics;

final class AnalyticsSnippet
{
    private const ASSETS_DIR = __DIR__ . '/../assets';

    public function __construct(private readonly Settings $settings)
    {
    }

    public function isConfigured(): bool
    {
        return $this->settings->isConfigured();
    }

    public function cspScriptHost(): string
    {
        return $this->settings->cspScriptHost();
    }

    public function renderHead(string $cspNonce): string
    {
        if (!$this->isConfigured()) {
            return '';
        }

        return match ($this->settings->provider) {
            Settings::PROVIDER_PLAUSIBLE => $this->renderPlausible($cspNonce),
            Settings::PROVIDER_MATOMO => $this->renderMatomo($cspNonce),
            default => '',
        };
    }

    private function renderPlausible(string $cspNonce): string
    {
        return $this->renderTemplate('plausible-snippet.html', [
            'SCRIPT_HOST' => $this->settings->scriptHost,
            'SITE_DOMAIN' => $this->settings->siteDomain,
            'NONCE' => $cspNonce,
        ]);
    }

    private function renderMatomo(string $cspNonce): string
    {
        $base = HostValidator::httpsBaseUrl($this->settings->matomoUrl);
        if ($base === null) {
            return '';
        }

        return $this->renderTemplate('matomo-snippet.html', [
            'NONCE' => $cspNonce,
            'TRACKER_URL' => $base . 'matomo.php',
            'SCRIPT_SRC' => $base . 'matomo.js',
            'SITE_ID' => $this->settings->matomoSiteId,
        ]);
    }

    /**
     * @param array<string, string> $vars
     */
    private function renderTemplate(string $filename, array $vars): string
    {
        $path = self::ASSETS_DIR . '/' . $filename;
        $template = file_get_contents($path);
        if (!is_string($template) || $template === '') {
            return '';
        }

        foreach ($vars as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $this->escape($value), $template);
        }

        return $template;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}