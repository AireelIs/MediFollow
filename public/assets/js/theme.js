/* Apply saved appearance before painting, then bind the accessible corner control. */
(() => {
  'use strict';
  const key = 'medifollow-theme';
  const preference = window.matchMedia('(prefers-color-scheme: dark)');
  let saved = null;
  try { saved = localStorage.getItem(key); } catch (_) { /* Storage may be disabled. */ }
  let chosen = saved === 'dark' || saved === 'light';
  function apply(theme) {
    document.documentElement.dataset.theme = theme;
    const button = document.getElementById('theme-toggle');
    if (!button) return;
    const dark = theme === 'dark';
    const label = dark ? 'Light mode' : 'Dark mode';
    button.setAttribute('aria-pressed', String(dark));
    button.setAttribute('aria-label', 'Switch to ' + label.toLowerCase());
    button.title = 'Switch to ' + label.toLowerCase();
    button.querySelector('.theme-label').textContent = label;
  }
  apply(chosen ? saved : (preference.matches ? 'dark' : 'light'));
  document.addEventListener('DOMContentLoaded', () => {
    apply(document.documentElement.dataset.theme);
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
      const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      chosen = true;
      try { localStorage.setItem(key, theme); } catch (_) { /* Still works for this page. */ }
      apply(theme);
    });
  });
  preference.addEventListener('change', event => {
    if (!chosen) apply(event.matches ? 'dark' : 'light');
  });
  window.addEventListener('storage', event => {
    if (event.key !== key) return;
    chosen = event.newValue === 'dark' || event.newValue === 'light';
    apply(chosen ? event.newValue : (preference.matches ? 'dark' : 'light'));
  });
})();
