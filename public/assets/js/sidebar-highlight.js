'use strict';
(() => {
  const nav = document.querySelector('.sidebar nav');
  const selected = nav?.querySelector('.nav.active');
  if (!nav || !selected) return;
  const links = Array.from(nav.querySelectorAll('.nav'));
  const route = link => {
    const url = new URL(link.href, location.href);
    return url.pathname + url.search;
  };
  const key = 'medifollow-sidebar-highlight';
  let previous;
  try { previous = sessionStorage.getItem(key); } catch (_) {}
  const startingLink = links.find(link => route(link) === previous) || selected;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  function position(link) {
    const outer = nav.getBoundingClientRect();
    const item = link.getBoundingClientRect();
    nav.style.setProperty('--nav-x', (item.left - outer.left) + 'px');
    nav.style.setProperty('--nav-y', (item.top - outer.top) + 'px');
    nav.style.setProperty('--nav-width', item.width + 'px');
    nav.style.setProperty('--nav-height', item.height + 'px');
  }
  nav.classList.add('sidebar-highlight');
  position(reduced.matches ? selected : startingLink);
  requestAnimationFrame(() => requestAnimationFrame(() => {
    nav.classList.add('highlight-ready');
    position(selected);
  }));
  try { sessionStorage.setItem(key, route(selected)); } catch (_) {}
  window.addEventListener('resize', () => position(selected));
  if (document.fonts?.ready) document.fonts.ready.then(() => { if (nav.classList.contains('highlight-ready')) position(selected); });
  window.addEventListener('pageshow', event => {
    if (event.persisted) {
      position(selected);
      try { sessionStorage.setItem(key, route(selected)); } catch (_) {}
    }
  });
})();
