(function () {
  'use strict';

  function refreshCaptcha() {
    var img = document.getElementById('captchaImg');
    if (!img) {
      return;
    }

    var base = img.getAttribute('data-captcha-url');
    if (!base) {
      return;
    }

    var sep = base.indexOf('?') >= 0 ? '&' : '?';
    img.src = base + sep + 't=' + Date.now();

    var input = document.getElementById('captcha');
    if (input) {
      input.value = '';
      input.focus();
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('captchaRefresh');
    if (btn) {
      btn.addEventListener('click', refreshCaptcha);
    }
  });
})();
