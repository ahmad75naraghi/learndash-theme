(() => {
  'use strict';

  const editor = document.querySelector('[data-ee-video-editor]');
  if (!editor) return;
  const list = editor.querySelector('[data-ee-video-list]');
  const template = editor.querySelector('[data-ee-video-template]');

  const reindex = () => {
    [...list.querySelectorAll('[data-ee-video-row]')].forEach((row, index) => {
      row.querySelectorAll('[name]').forEach(field => {
        field.name = field.name.replace(/evented_video_playlist\[[^\]]+\]/, `evented_video_playlist[${index}]`);
      });
    });
  };

  const bindRow = row => {
    const title = row.querySelector('[data-ee-video-title]');
    const heading = row.querySelector('[data-ee-video-row-title]');
    title?.addEventListener('input', () => {
      heading.textContent = title.value.trim() || 'قسمت جدید';
    });

    row.querySelector('[data-ee-video-remove]')?.addEventListener('click', () => {
      const hasContent = [...row.querySelectorAll('input:not([type="hidden"]), textarea')].some(field => field.value.trim());
      if (!hasContent || window.confirm('این قسمت از فهرست حذف شود؟')) {
        row.remove();
        reindex();
      }
    });
    row.querySelector('[data-ee-video-up]')?.addEventListener('click', () => {
      if (row.previousElementSibling) list.insertBefore(row, row.previousElementSibling);
      reindex();
    });
    row.querySelector('[data-ee-video-down]')?.addEventListener('click', () => {
      if (row.nextElementSibling) list.insertBefore(row.nextElementSibling, row);
      reindex();
    });
    row.querySelector('[data-ee-video-media]')?.addEventListener('click', () => {
      if (!window.wp?.media) return;
      const frame = wp.media({ title: 'انتخاب تصویر ویدئو', button: { text: 'استفاده از این تصویر' }, multiple: false, library: { type: 'image' } });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        const imageUrl = attachment.sizes?.medium?.url || attachment.url || '';
        row.querySelector('[data-ee-video-thumb-id]').value = attachment.id || 0;
        row.querySelector('[data-ee-video-thumb-url]').value = imageUrl;
        const preview = row.querySelector('[data-ee-video-preview]');
        preview.replaceChildren();
        if (imageUrl) {
          const image = document.createElement('img');
          image.src = imageUrl;
          image.alt = '';
          preview.append(image);
        }
      });
      frame.open();
    });
  };

  list.querySelectorAll('[data-ee-video-row]').forEach(bindRow);
  editor.querySelector('[data-ee-video-add]')?.addEventListener('click', () => {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(list.children.length)).trim();
    const row = wrapper.firstElementChild;
    if (!row) return;
    list.append(row);
    bindRow(row);
    reindex();
    row.querySelector('[data-ee-video-title]')?.focus();
  });

  document.querySelector('#post')?.addEventListener('submit', reindex);
})();
