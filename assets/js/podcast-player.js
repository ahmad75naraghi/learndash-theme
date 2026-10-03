(function () {
  'use strict';

  var root = document.querySelector('[data-ee-podcast-player]');
  var audio = root && root.querySelector('[data-ee-podcast-audio]');
  var title = root && root.querySelector('[data-ee-podcast-title]');
  var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-ee-podcast-src]'));

  if (!root || !audio || !buttons.length) return;

  buttons.forEach(function (button) {
    button.addEventListener('click', function () {
      var source = button.getAttribute('data-ee-podcast-src');
      if (!source) return;

      buttons.forEach(function (item) { item.removeAttribute('aria-current'); });
      button.setAttribute('aria-current', 'true');
      audio.src = source;
      audio.load();
      if (title) title.textContent = button.getAttribute('data-ee-podcast-name') || '';

      var play = audio.play();
      if (play && typeof play.catch === 'function') play.catch(function () {});
      root.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
    });
  });
})();
