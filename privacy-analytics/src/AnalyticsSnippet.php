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
        $host = $this->escape($this->settings->scriptHost);
        $domain = $this->escape($this->settings->siteDomain);
        $nonce = $this->escape($cspNonce);

        return '<script defer src="https://' . $host . '/js/script.js" '
            . 'data-domain="' . $domain . '" nonce="' . $nonce . '"></script>';
    }

    private function renderMatomo(string $cspNonce): string
    {
        $base = HostValidator::httpsBaseUrl($this->settings->matomoUrl);
        if ($base === null) {
            return '';
        }

        $trackerPhp = $this->escape($base . 'matomo.php');
        $scriptSrc = $this->escape($base . 'matomo.js');
        $siteId = $this->escape($this->settings->matomoSiteId);
        $nonce = $this->escape($cspNonce);

        return '<script nonce="' . $nonce . '">'
            . 'var _paq=window._paq=window._paq||[];'
            . '_paq.push(["trackPageView"]);'
            . '_paq.push(["enableLinkTracking"]);'
            . '_paq.push(["setTrackerUrl","' . $trackerPhp . '"]);'
            . '_paq.push(["setSiteId","' . $siteId . '"]);'
            . '</script>'
            . '<script defer src="' . $scriptSrc . '" nonce="' . $nonce . '"></script>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}