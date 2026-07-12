/**
 * Copyright (c) 2026 Latch contributors
 * SPDX-License-Identifier: MIT
 */
(function () {
    'use strict';

    var overlay = null;

    function closeLightbox() {
        if (!overlay) {
            return;
        }

        overlay.remove();
        overlay = null;
        document.body.classList.remove('post-image-lightbox-open');
    }

    function openLightbox(src, alt) {
        closeLightbox();

        overlay = document.createElement('div');
        overlay.className = 'post-image-lightbox';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', alt || 'Full size image');

        var img = document.createElement('img');
        img.src = src;
        img.alt = alt || '';
        img.className = 'post-image-lightbox__img';
        img.decoding = 'async';

        overlay.appendChild(img);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeLightbox();
            }
        });

        document.body.appendChild(overlay);
        document.body.classList.add('post-image-lightbox-open');
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeLightbox();
        }
    });

    document.addEventListener('click', function (event) {
        var btn = event.target.closest('.post-image-open');
        if (!btn) {
            return;
        }

        event.preventDefault();

        var src = btn.getAttribute('data-full-src');
        if (!src) {
            return;
        }

        var preview = btn.querySelector('img');
        var alt = preview ? preview.getAttribute('alt') : '';

        openLightbox(src, alt || '');
    });
})();