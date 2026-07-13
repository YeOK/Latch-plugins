/**
 * Copyright (c) 2026 Latch contributors
 * SPDX-License-Identifier: MIT
 */
(function () {
    'use strict';

    var ALLOWED = /^https:\/\/(www\.youtube-nocookie\.com\/embed\/[A-Za-z0-9_-]+|player\.vimeo\.com\/video\/\d+)/;

    function mountEmbed(el, autoplay) {
        if (el.getAttribute('data-embed-mounted') === '1') {
            return;
        }

        var src = el.getAttribute('data-embed-src');
        if (!src || !ALLOWED.test(src)) {
            return;
        }

        if (autoplay) {
            src += src.indexOf('?') === -1 ? '?autoplay=1' : '&autoplay=1';
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
        el.style.backgroundImage = '';
        el.textContent = '';
        el.appendChild(iframe);
    }

    function initEmbeds(root) {
        var nodes = root.querySelectorAll('.link-embed[data-embed-src]');
        for (var i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if (el.getAttribute('data-embed-mounted') === '1') {
                continue;
            }

            var poster = el.getAttribute('data-embed-poster');
            if (poster) {
                el.style.backgroundImage = 'url("' + poster.replace(/"/g, '\\"') + '")';
            }

            var btn = el.querySelector('.link-embed-play');
            if (!btn) {
                continue;
            }

            btn.addEventListener('click', function () {
                mountEmbed(this.closest('.link-embed'), true);
            });
        }
    }

    function init() {
        initEmbeds(document);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();