(() => {
  window.addEventListener('load', () => {
    const p = new URLSearchParams(window.location.search);
    if (p.get('print') === '1') {
      setTimeout(() => { window.print(); }, 150);
    }
  });
})();