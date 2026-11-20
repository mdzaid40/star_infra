/* Blog post rich-text editor (Quill 2). Falls back to a plain textarea when Quill can't load. */
(function () {
  'use strict';

  var holder = document.getElementById('editor');
  var textarea = document.getElementById('content');
  var form = document.getElementById('post-form');
  if (!holder || !textarea || !form || typeof window.Quill === 'undefined') return;

  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var uploadUrl = form.getAttribute('data-upload-url');

  holder.hidden = false;
  textarea.hidden = true;

  var quill = new Quill(holder, {
    theme: 'snow',
    placeholder: 'Start writing your article…',
    modules: {
      toolbar: {
        container: [
          [{ header: [2, 3, 4, false] }],
          ['bold', 'italic', 'underline', 'strike'],
          [{ list: 'ordered' }, { list: 'bullet' }],
          ['blockquote', 'code-block', 'link', 'image'],
          [{ align: [] }],
          ['clean']
        ],
        handlers: { image: chooseImage }
      }
    }
  });

  function chooseImage() {
    var input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp,image/gif';
    input.addEventListener('change', function () {
      if (input.files && input.files[0]) upload(input.files[0]);
    });
    input.click();
  }

  function upload(file) {
    var range = quill.getSelection(true);
    var data = new FormData();
    data.append('image', file);
    data.append('_token', csrf);
    quill.enable(false);
    fetch(uploadUrl, { method: 'POST', body: data, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } })
      .then(function (r) { return r.json().catch(function () { return { error: 'Upload failed (server error ' + r.status + ').' }; }); })
      .then(function (res) {
        if (!res.url) throw new Error(res.error || 'Upload failed.');
        quill.insertEmbed(range ? range.index : quill.getLength(), 'image', res.url, 'user');
        quill.setSelection((range ? range.index : quill.getLength()) + 1, 0);
      })
      .catch(function (err) { window.alert(err.message); })
      .then(function () { quill.enable(true); });
  }

  // Images pasted/dropped as base64 would bloat the database: upload them instead.
  quill.root.addEventListener('paste', function (e) {
    var items = (e.clipboardData && e.clipboardData.files) || [];
    if (items.length && /^image\//.test(items[0].type)) { e.preventDefault(); upload(items[0]); }
  }, true);
  quill.root.addEventListener('drop', function (e) {
    var files = e.dataTransfer && e.dataTransfer.files;
    if (files && files.length && /^image\//.test(files[0].type)) { e.preventDefault(); upload(files[0]); }
  }, true);

  form.addEventListener('submit', function () {
    var html = quill.getLength() > 1 ? quill.getSemanticHTML() : '';
    // Quill 2.0.x emits &nbsp; for every space in getSemanticHTML(); normalise them.
    textarea.value = html.replace(/&nbsp;/g, ' ').replace(/ /g, ' ');
  });

  quill.on('text-change', function () { form.dispatchEvent(new Event('input')); });
})();
