(function () {
  'use strict';

  // Sidebar toggle (mobile)
  var toggle = document.querySelector('.sidebar-toggle');
  var sidebar = document.getElementById('admin-sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      var open = sidebar.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(open));
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { sidebar.classList.remove('is-open'); toggle.setAttribute('aria-expanded', 'false'); }
    });
  }

  // Confirm dangerous form submissions
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
    });
  });
  document.querySelectorAll('[data-confirm-click]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      if (!window.confirm(btn.getAttribute('data-confirm-click'))) e.preventDefault();
    });
  });

  // "Select all" checkbox
  document.querySelectorAll('[data-check-all]').forEach(function (master) {
    master.addEventListener('change', function () {
      var form = master.closest('form');
      form.querySelectorAll('input[name="ids[]"]').forEach(function (cb) { cb.checked = master.checked; });
    });
  });

  // Image preview before upload
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    input.addEventListener('change', function () {
      var wrap = input.closest('.image-field');
      var preview = wrap && wrap.querySelector('.image-preview');
      var img = preview && preview.querySelector('img');
      if (!img || !input.files || !input.files[0]) return;
      img.src = URL.createObjectURL(input.files[0]);
      preview.hidden = false;
    });
  });

  // Auto-generate slugs from the title until the slug is edited by hand
  var title = document.getElementById('title');
  var slug = document.getElementById('slug');
  if (title && slug) {
    var slugify = function (s) {
      return s.toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 180);
    };
    title.addEventListener('input', function () {
      if (slug.getAttribute('data-autoslug') === '1') slug.value = slugify(title.value);
    });
    slug.addEventListener('input', function () {
      slug.setAttribute('data-autoslug', slug.value === '' ? '1' : '0');
      var clean = slugify(slug.value);
      if (clean !== slug.value && !/-$/.test(slug.value)) slug.value = clean;
    });
  }

  // Warn before leaving a page with unsaved form changes
  document.querySelectorAll('form.editor-layout, form.settings-form').forEach(function (form) {
    var dirty = false;
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
    window.addEventListener('beforeunload', function (e) {
      if (dirty) { e.preventDefault(); e.returnValue = ''; }
    });
  });
})();
