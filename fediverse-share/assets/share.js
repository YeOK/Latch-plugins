/**
 * Fediverse share — fixed panel placement (escapes .topic-view overflow:hidden),
 * instance memory, share URLs, copy, Web Share API.
 * Copyright (c) 2026 Latch contributors — MIT
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'latch.fediverse.instance';
  var GAP = 8;

  function normalizeInstance(raw) {
    if (!raw || typeof raw !== 'string') {
      return '';
    }
    var s = raw.trim().replace(/^https?:\/\//i, '');
    s = s.split('/')[0].split('?')[0].replace(/\.$/, '').toLowerCase();
    if (!s || s.length > 253) {
      return '';
    }
    if (!/^[a-z0-9][a-z0-9.-]*[a-z0-9]$/i.test(s) && s !== 'localhost' && !/^localhost:\d+$/.test(s)) {
      return '';
    }
    return s;
  }

  function mastodonUrl(instance, text) {
    return 'https://' + instance + '/share?text=' + encodeURIComponent(text);
  }

  function misskeyUrl(instance, text) {
    return 'https://' + instance + '/share?text=' + encodeURIComponent(text);
  }

  function loadRemembered() {
    try {
      return normalizeInstance(localStorage.getItem(STORAGE_KEY) || '');
    } catch (e) {
      return '';
    }
  }

  function saveRemembered(host) {
    try {
      if (host) {
        localStorage.setItem(STORAGE_KEY, host);
      }
    } catch (e) {
      /* private mode */
    }
  }

  function setStatus(root, msg, ok) {
    var el = root.querySelector('[data-fedi-status]');
    if (!el) {
      return;
    }
    el.hidden = !msg;
    el.textContent = msg || '';
    el.classList.toggle('is-ok', !!ok);
    el.classList.toggle('is-err', !!msg && !ok);
  }

  function getInstance(root) {
    var input = root.querySelector('.latch-fedi-share__instance');
    var fromInput = input ? normalizeInstance(input.value) : '';
    if (fromInput) {
      return fromInput;
    }
    var def = normalizeInstance(root.getAttribute('data-default-instance') || '');
    return def || loadRemembered();
  }

  function placePanel(root) {
    var summary = root.querySelector('.latch-fedi-share__summary');
    var panel = root.querySelector('.latch-fedi-share__panel');
    if (!summary || !panel || !root.open) {
      return;
    }

    panel.classList.add('is-fixed');
    // Reset so size measurement is stable
    panel.style.left = '0';
    panel.style.top = '0';
    panel.style.visibility = 'hidden';

    var rect = summary.getBoundingClientRect();
    var pw = panel.offsetWidth || 320;
    var ph = panel.offsetHeight || 200;
    var vw = window.innerWidth;
    var vh = window.innerHeight;

    var left = rect.right - pw;
    if (left < GAP) {
      left = GAP;
    }
    if (left + pw > vw - GAP) {
      left = Math.max(GAP, vw - pw - GAP);
    }

    var top = rect.bottom + GAP;
    if (top + ph > vh - GAP) {
      // Prefer above the button when not enough room below
      var above = rect.top - ph - GAP;
      if (above >= GAP) {
        top = above;
      } else {
        top = Math.max(GAP, vh - ph - GAP);
      }
    }

    panel.style.left = Math.round(left) + 'px';
    panel.style.top = Math.round(top) + 'px';
    panel.style.visibility = '';
  }

  function clearPanelPlacement(root) {
    var panel = root.querySelector('.latch-fedi-share__panel');
    if (!panel) {
      return;
    }
    panel.classList.remove('is-fixed');
    panel.style.left = '';
    panel.style.top = '';
    panel.style.visibility = '';
  }

  function closeOthers(except) {
    document.querySelectorAll('details.latch-fedi-share[open]').forEach(function (el) {
      if (el !== except) {
        el.open = false;
        clearPanelPlacement(el);
      }
    });
  }

  function bindRoot(root) {
    if (root.getAttribute('data-fedi-bound') === '1') {
      return;
    }
    root.setAttribute('data-fedi-bound', '1');

    var input = root.querySelector('.latch-fedi-share__instance');
    var remembered = loadRemembered();
    if (input && remembered && !input.value) {
      input.value = remembered;
    }

    var webBtn = root.querySelector('[data-fedi-action="web"]');
    if (webBtn && typeof navigator.share === 'function') {
      webBtn.hidden = false;
    }

    root.addEventListener('toggle', function () {
      if (root.open) {
        closeOthers(root);
        // Two frames: layout open state, then measure
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            placePanel(root);
          });
        });
      } else {
        clearPanelPlacement(root);
      }
    });

    root.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-fedi-action]');
      if (!btn || !root.contains(btn)) {
        return;
      }
      ev.preventDefault();
      ev.stopPropagation();

      var action = btn.getAttribute('data-fedi-action');
      var text = root.getAttribute('data-share-text') || '';
      var url = root.getAttribute('data-share-url') || '';
      var title = root.getAttribute('data-share-title') || '';

      if (action === 'copy') {
        var toCopy = url || text;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(toCopy).then(function () {
            setStatus(root, 'Link copied', true);
          }).catch(function () {
            setStatus(root, 'Could not copy', false);
          });
        } else {
          setStatus(root, 'Clipboard unavailable', false);
        }
        return;
      }

      if (action === 'web') {
        if (typeof navigator.share !== 'function') {
          setStatus(root, 'Web Share not supported', false);
          return;
        }
        navigator.share({ title: title, text: text, url: url }).then(function () {
          setStatus(root, 'Shared', true);
        }).catch(function () {
          /* user cancelled */
        });
        return;
      }

      var instance = getInstance(root);
      if (!instance) {
        setStatus(root, 'Enter your instance host', false);
        if (input) {
          input.focus();
        }
        return;
      }

      saveRemembered(instance);
      if (input) {
        input.value = instance;
      }

      var shareUrl = '';
      if (action === 'mastodon') {
        shareUrl = mastodonUrl(instance, text);
      } else if (action === 'misskey') {
        shareUrl = misskeyUrl(instance, text);
      }

      if (!shareUrl) {
        setStatus(root, 'Invalid instance', false);
        return;
      }

      setStatus(root, '', true);
      window.open(shareUrl, '_blank', 'noopener,noreferrer');
    });
  }

  function onScrollOrResize() {
    document.querySelectorAll('details.latch-fedi-share[open]').forEach(placePanel);
  }

  function init() {
    document.querySelectorAll('[data-latch-fedi-share]').forEach(bindRoot);
    window.addEventListener('resize', onScrollOrResize);
    window.addEventListener('scroll', onScrollOrResize, true);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
