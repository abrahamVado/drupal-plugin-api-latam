(function () {
  'use strict';

  function buildUrl(base, params) {
    try {
      var u = new URL(base, window.location.origin);
      Object.keys(params || {}).forEach(function (k) {
        var v = params[k];
        if (v !== undefined && v !== null) u.searchParams.set(k, String(v));
      });
      return u.toString();
    } catch (e) {
      console.warn('IndiVideo: invalid URL', e);
      return null;
    }
  }

  function renderIframe(container) {
    var base = container.getAttribute('data-video-base');
    var raw = container.getAttribute('data-vars');
    var vars = {};
    try { vars = JSON.parse(raw); } catch (e) {}

    var url = buildUrl(base, vars);
    if (!url) return;

    var iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.width = '100%';
    iframe.height = '540';
    iframe.setAttribute('allowfullscreen', 'true');
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    iframe.style.border = '0';

    container.innerHTML = '';
    container.appendChild(iframe);
  }

  function init() {
    document.querySelectorAll('.individeo-embed').forEach(renderIframe);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
