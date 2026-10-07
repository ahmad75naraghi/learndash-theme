(() => {
  'use strict';
  const editor = document.querySelector('[data-ee-download-editor]');
  if (!editor) return;
  const list = editor.querySelector('[data-ee-download-list]');
  const template = editor.querySelector('[data-ee-download-template]');

  const reindex = () => {
    [...list.querySelectorAll('[data-ee-download-row]')].forEach((row, index) => {
      row.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace(/evented_download_files\[[^\]]+\]/, `evented_download_files[${index}]`);
      });
    });
  };
  const bind = row => {
    const title = row.querySelector('[data-ee-download-title]');
    title?.addEventListener('input', () => {
      row.querySelector('[data-ee-download-heading]').textContent = title.value.trim() || row.querySelector('[data-ee-download-file-name]').textContent || 'فایل جدید';
    });
    const externalUrl = row.querySelector('input[type="url"]');
    externalUrl?.addEventListener('input', () => {
      if (externalUrl.value.trim()) {
        row.querySelector('[data-ee-download-attachment]').value = 0;
        row.querySelector('[data-ee-download-legacy]').value = '';
        row.querySelector('[data-ee-download-file-name]').textContent = externalUrl.value.trim();
      }
    });
    row.querySelector('[data-ee-download-remove]')?.addEventListener('click', () => {
      const hasContent = [...row.querySelectorAll('input:not([type="hidden"])')].some(field => field.value.trim());
      if (!hasContent || window.confirm('این فایل از فهرست حذف شود؟')) { row.remove(); reindex(); }
    });
    row.querySelector('[data-ee-download-up]')?.addEventListener('click', () => { if (row.previousElementSibling) list.insertBefore(row, row.previousElementSibling); reindex(); });
    row.querySelector('[data-ee-download-down]')?.addEventListener('click', () => { if (row.nextElementSibling) list.insertBefore(row.nextElementSibling, row); reindex(); });
    row.querySelector('[data-ee-download-media]')?.addEventListener('click', () => {
      if (!window.wp?.media) return;
      const frame = wp.media({ title: 'انتخاب فایل دانلود', button: { text: 'استفاده از این فایل' }, multiple: false });
      frame.on('select', () => {
        const file = frame.state().get('selection').first().toJSON();
        row.querySelector('[data-ee-download-attachment]').value = file.id || 0;
        row.querySelector('[data-ee-download-legacy]').value = '';
        row.querySelector('input[type="url"]').value = '';
        row.querySelector('[data-ee-download-file-name]').textContent = file.filename || file.title || 'فایل انتخاب‌شده';
        if (!title.value.trim()) { title.value = file.title || file.filename || ''; title.dispatchEvent(new Event('input')); }
      });
      frame.open();
    });
  };

  list.querySelectorAll('[data-ee-download-row]').forEach(bind);
  editor.querySelector('[data-ee-download-add]')?.addEventListener('click', () => {
    const holder = document.createElement('div');
    holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(list.children.length)).trim();
    const row = holder.firstElementChild;
    if (!row) return;
    list.append(row); bind(row); reindex(); row.querySelector('[data-ee-download-title]')?.focus();
  });
  document.querySelector('#post')?.addEventListener('submit', reindex);
})();
