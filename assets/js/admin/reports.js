(function () {
  'use strict';
  var printButton = document.querySelector('[data-ee-report-print]');
  if (printButton) printButton.addEventListener('click', function () { window.print(); });
})();
