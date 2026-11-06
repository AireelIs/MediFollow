'use strict';
// Forms submit to PHP; this file only handles dialogs and presentation.
const detailDialog = document.getElementById('record-dialog');
const createDialog = document.getElementById('create-dialog');
document.addEventListener('click', event => {
  const open = event.target.closest('[data-record]');
  if (open) {
    const template = document.getElementById('record-' + open.dataset.record);
    if (template && detailDialog) {
      event.preventDefault();
      detailDialog.replaceChildren(template.content.cloneNode(true));
      detailDialog.showModal();
    }
  }
  if (event.target.closest('[data-new-followup]') && createDialog) createDialog.showModal();
  const close = event.target.closest('[data-close]');
  if (close) close.closest('dialog').close();
});
const search = document.getElementById('search');
const statusFilter = document.getElementById('filter');
function filterRows() {
  const query = search.value.trim().toLowerCase();
  const filter = statusFilter.value;
  let count = 0;
  document.querySelectorAll('[data-search]').forEach(row => {
    const allowed = filter === 'all' || row.dataset.status === filter ||
      (filter === 'attention' && row.dataset.attention === '1') ||
      (filter === 'unanswered' && row.dataset.unanswered === '1');
    row.hidden = !(allowed && row.dataset.search.includes(query));
    if (!row.hidden) count++;
  });
  const empty = document.getElementById('no-client-matches');
  if (empty) empty.hidden = count !== 0;
  const counter = document.getElementById('visible-count');
  if (counter) counter.textContent = count;
}
if (search && statusFilter) {
  search.addEventListener('input', filterRows);
  statusFilter.addEventListener('change', filterRows);
}

// Open a row's usual details without intercepting its own buttons or text selection.
function openFollowUpRow(row) {
  window.location.assign(row.dataset.rowHref);
}
document.addEventListener('click', event => {
  const row = event.target.closest('.followup-clickable-row');
  if (!row || event.target.closest('a,button,input,select,textarea,label')) return;
  if (window.getSelection()?.toString()) return;
  openFollowUpRow(row);
});
document.addEventListener('keydown', event => {
  const row = event.target.closest('.followup-clickable-row');
  if (!row || event.target !== row || !['Enter', ' '].includes(event.key)) return;
  event.preventDefault();
  if (!event.repeat) openFollowUpRow(row);
});
