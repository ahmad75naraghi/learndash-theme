(() => {
  'use strict';

  document.querySelectorAll('[data-ee-video-player]').forEach(player => {
    const video = player.querySelector('video');
    const source = video?.querySelector('source');
    const title = player.querySelector('[data-ee-video-title]');
    const buttons = document.querySelectorAll('[data-ee-video-src]');
    if (!video || !source || !buttons.length) return;

    buttons.forEach(button => {
      button.addEventListener('click', () => {
        const src = button.dataset.eeVideoSrc || '';
        if (!src) return;
        source.src = src;
        if (button.dataset.eeVideoPoster) video.poster = button.dataset.eeVideoPoster;
        else video.removeAttribute('poster');
        if (title) title.textContent = button.querySelector('strong')?.textContent?.trim() || '';
        buttons.forEach(item => item.removeAttribute('aria-current'));
        button.setAttribute('aria-current', 'true');
        video.load();
        video.play().catch(() => {});
        player.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
      });
    });
  });
})();
