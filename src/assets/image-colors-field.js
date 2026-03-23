/**
 * Click-to-copy for color swatches in the Image Colors field.
 */
(function () {
  document.addEventListener('click', function (e) {
    const swatch = e.target.closest('.image-colors-swatch');
    if (!swatch) return;

    const hex = swatch.dataset.hex;
    if (!hex) return;

    navigator.clipboard.writeText(hex).then(function () {
      Craft.cp.displayNotice(Craft.t('app', '{hex} copied to clipboard.', { hex: hex }));
    });
  });
})();
