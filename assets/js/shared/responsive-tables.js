(function () {
  'use strict';

  function clean(text) {
    return (text || '').replace(/\s+/g, ' ').trim();
  }

  function applyLabels(table) {
    if (!table || table.dataset.sgceResponsiveLabels === '1') return;
    var headers = Array.prototype.map.call(table.querySelectorAll('thead th'), function (th) {
      return clean(th.textContent);
    });
    if (!headers.length) return;
    Array.prototype.forEach.call(table.querySelectorAll('tbody tr'), function (row) {
      Array.prototype.forEach.call(row.children, function (cell, index) {
        if (!cell || cell.tagName !== 'TD') return;
        if (!cell.hasAttribute('data-label')) {
          cell.setAttribute('data-label', headers[index] || 'Detalle');
        }
      });
    });
    table.dataset.sgceResponsiveLabels = '1';
  }

  function run() {
    Array.prototype.forEach.call(document.querySelectorAll('.table-responsive table, table.SgceTable, table.table'), applyLabels);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }

  window.addEventListener('sgce:table-updated', run);

  if ('MutationObserver' in window) {
    var observer = new MutationObserver(function (mutations) {
      var needsRun = mutations.some(function (mutation) {
        return Array.prototype.some.call(mutation.addedNodes || [], function (node) {
          return node && node.nodeType === 1 && (node.matches && (node.matches('table') || node.matches('tr') || node.querySelector('table,tr')));
        });
      });
      if (needsRun) run();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
  }
})();
