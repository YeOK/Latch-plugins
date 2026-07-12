/**
 * Copyright (c) 2026 Latch contributors
 * SPDX-License-Identifier: MIT
 */
(function () {
    'use strict';

    var ALLOWED = /^https:\/\/(www\.youtube-nocookie\.com\/embed\/[A-Za-z0-9_-]+|player\.vimeo\.com\/video\/\d+)/;

    function mountEmbeds(root) {
        var nodes = root.querySelectorAll('.link-embed[data-embed-src]');
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.getAttribute('data-embed-mounted') === '1') {
                continue;
            }

            var src = el.getAttribute('data-embed-src');
            if (!src || !ALLOWED.test(src)) {
                continue;
            }

            var iframe = document.createElement('iframe');
            iframe.setAttribute('src', src);
            iframe.setAttribute('title', el.getAttribute('data-embed-title') || 'Embedded video');
            iframe.setAttribute('loading', 'lazy');
            iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            iframe.setAttribute('allowfullscreen', '');

            if (src.indexOf('youtube-nocookie.com') !== -1) {
                iframe.setAttribute(
                    'allow',
                    'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share',
                );
            } else {
                iframe.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
            }

            el.setAttribute('data-embed-mounted', '1');
            el.textContent = '';
            el.appendChild(iframe);
        }
    }

    function init() {
        mountEmbeds(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();