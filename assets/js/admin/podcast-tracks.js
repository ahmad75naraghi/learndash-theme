(function () {
  'use strict';

  var root = document.querySelector('[data-ee-podcast-editor]');
  if (!root) return;
  var list = root.querySelector('[data-ee-podcast-list]');
  var template = root.querySelector('[data-ee-podcast-template]');
  var add = root.querySelector('[data-ee-podcast-add]');

  function reindex() {
    Array.prototype.forEach.call(list.querySelectorAll('[data-ee-podcast-row]'), function (row, index) {
      Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (field) {
        field.name = field.name.replace(/evented_podcast_tracks\[[^\]]+\]/, 'evented_podcast_tracks[' + index + ']');
      });
    });
  }

  function updateButtons() {
    var rows = list.querySelectorAll('[data-ee-podcast-row]');
    Array.prototype.forEach.call(rows, function (row, index) {
      var up = row.querySelector('[data-ee-podcast-up]');
      var down = row.querySelector('[data-ee-podcast-down]');
      if (up) up.disabled = index === 0;
      if (down) down.disabled = index === rows.length - 1;
    });
  }

  function bind(row) {
    var title = row.querySelector('[data-ee-podcast-title]');
    var heading = row.querySelector('[data-ee-podcast-heading]');
    var attachment = row.querySelector('[data-ee-podcast-attachment]');
    var url = row.querySelector('[data-ee-podcast-url]');
    var file = row.querySelector('[data-ee-podcast-file]');

    if (title) title.addEventListener('input', function () { heading.textContent = title.value || file.textContent || 'قسمت جدید'; });
    if (url) url.addEventListener('input', function () { if (url.value) { attachment.value = '0'; file.textContent = 'URL خارجی'; } });
    row.querySelector('[data-ee-podcast-remove]').addEventListener('click', function () { row.remove(); reindex(); updateButtons(); });
    row.querySelector('[data-ee-podcast-up]').addEventListener('click', function () { if (row.previousElementSibling) list.insertBefore(row, row.previousElementSibling); reindex(); updateButtons(); });
    row.querySelector('[data-ee-podcast-down]').addEventListener('click', function () { if (row.nextElementSibling) list.insertBefore(row.nextElementSibling, row); reindex(); updateButtons(); });
    row.querySelector('[data-ee-podcast-media]').addEventListener('click', function () {
      var frame = wp.media({ title: 'انتخاب فایل صوتی', button: { text: 'استفاده از این فایل' }, library: { type: 'audio' }, multiple: false });
      frame.on('select', function () {
        var item = frame.state().get('selection').first().toJSON();
        attachment.value = item.id || 0;
        url.value = '';
        file.textContent = item.filename || item.title || 'فایل صوتی انتخاب شد';
        if (!title.value) { title.value = item.title || ''; heading.textContent = title.value || file.textContent; }
      });
      frame.open();
    });
  }

  Array.prototype.forEach.call(list.querySelectorAll('[data-ee-podcast-row]'), bind);
  updateButtons();
  add.addEventListener('click', function () {
    var holder = document.createElement('div');
    holder.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(list.children.length)).trim();
    var row = holder.firstElementChild;
    list.appendChild(row);
    bind(row); reindex(); updateButtons();
    var input = row.querySelector('input[type="text"]');
    if (input) input.focus();
  });
})();
