(function () {
  'use strict';

  /* ---------- Mobile navigation ---------- */
  var toggle = document.querySelector('.menu-toggle');
  var nav = document.getElementById('main-nav');
  var desktop = window.matchMedia('(min-width: 992px)');

  function setOpen(open) {
    if (!toggle || !nav) return;
    nav.classList.toggle('is-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    document.body.classList.toggle('nav-open', open);
  }

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      setOpen(!nav.classList.contains('is-open'));
    });

    nav.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        setOpen(false);
        toggle.focus();
      }
    });

    document.addEventListener('click', function (e) {
      if (nav.classList.contains('is-open') && !nav.contains(e.target) && !toggle.contains(e.target)) setOpen(false);
    });

    var onChange = function (mq) { if (mq.matches) setOpen(false); };
    if (desktop.addEventListener) desktop.addEventListener('change', onChange);
    else if (desktop.addListener) desktop.addListener(onChange); // Safari < 14
  }

  /* ---------- Contact form: friendly client-side validation ---------- */
  var form = document.getElementById('enquiry-form');
  if (form) {
    form.addEventListener('submit', function (e) {
      var firstInvalid = null;
      form.querySelectorAll('input[required], textarea[required], input[type="email"], input[type="tel"]').forEach(function (field) {
        var label = field.closest('label');
        var msg = label ? label.querySelector('.field-error.js') : null;
        var valid = field.checkValidity();
        field.setAttribute('aria-invalid', valid ? 'false' : 'true');
        if (!valid) {
          if (!msg && label) {
            msg = document.createElement('span');
            msg.className = 'field-error js';
            label.appendChild(msg);
          }
          if (msg) msg.textContent = field.validationMessage;
          firstInvalid = firstInvalid || field;
        } else if (msg) {
          msg.remove();
        }
      });
      if (firstInvalid) {
        e.preventDefault();
        firstInvalid.focus();
        return;
      }
      var btn = form.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; btn.firstChild.textContent = 'Sending… '; }
    });
    // Re-enable the button if the user comes back via the browser's back button.
    window.addEventListener('pageshow', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn && btn.disabled) { btn.disabled = false; btn.firstChild.textContent = 'Send enquiry '; }
    });
  }
})();
