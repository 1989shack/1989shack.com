// Simple builder: add/remove/edit/reorder/export
const canvas = document.getElementById('canvas');
const tmpl = document.getElementById('card-template');

document.getElementById('add-card').addEventListener('click', addCard);
document.getElementById('add-button').addEventListener('click', addStandaloneButton);
document.getElementById('clear').addEventListener('click', () => canvas.innerHTML = '');
document.getElementById('export').addEventListener('click', exportHTML);

// Delegation: remove and image editing
canvas.addEventListener('click', (e) => {
  if (e.target.matches('.remove')) e.target.closest('.card').remove();
  if (e.target.matches('img')) {
    const url = prompt('Image URL:', e.target.src);
    if (url) e.target.src = url;
  }
});

// Drag & drop reordering
let dragEl = null;
canvas.addEventListener('dragstart', (e) => {
  const c = e.target.closest('.card');
  if (!c) return;
  dragEl = c;
  c.setAttribute('dragging', '');
});
canvas.addEventListener('dragend', (e) => {
  if (dragEl) dragEl.removeAttribute('dragging');
  dragEl = null;
});
canvas.addEventListener('dragover', (e) => {
  e.preventDefault();
  const after = getDragAfterElement(canvas, e.clientY);
  if (after == null) canvas.appendChild(dragEl);
  else canvas.insertBefore(dragEl, after);
});
function getDragAfterElement(container, y) {
  const cards = [...container.querySelectorAll('.card:not([dragging])')];
  return cards.reduce((closest, child) => {
    const box = child.getBoundingClientRect();
    const offset = y - box.top - box.height / 2;
    if (offset < 0 && offset > closest.offset) return { offset, element: child };
    return closest;
  }, { offset: Number.NEGATIVE_INFINITY }).element;
}

// helpers
function addCard() {
  const node = tmpl.content.firstElementChild.cloneNode(true);
  // small accessibility: ensure contenteditable fields are navigable
  node.querySelectorAll('[contenteditable]').forEach(el => el.setAttribute('aria-label','editable'));
  canvas.appendChild(node);
}
function addStandaloneButton() {
  const wrapper = document.createElement('div');
  wrapper.className = 'card';
  wrapper.innerHTML = `
    <div style="padding:20px;text-align:center">
      <button class="btn" contenteditable="true">New Button</button>
    </div>
    <button class="remove" title="Remove">✕</button>
  `;
  canvas.appendChild(wrapper);
}

// Export: clean builder-only attributes and produce full HTML file
function exportHTML() {
  // clone to avoid modifying live canvas
  const clone = canvas.cloneNode(true);
  // remove builder controls (remove buttons, draggable, aria)
  clone.querySelectorAll('.remove').forEach(n => n.remove());
  clone.querySelectorAll('[dragging]').forEach(n => n.removeAttribute('dragging'));
  clone.querySelectorAll('[contenteditable]').forEach(n => n.removeAttribute('contenteditable'));
  clone.querySelectorAll('[aria-label]').forEach(n => n.removeAttribute('aria-label'));
  clone.querySelectorAll('img').forEach(img => {
    if (!img.src) img.src = '';
  });

  const html = `<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Exported Page</title>
<style>
/* minimal exported styles */
body{font-family:system-ui, -apple-system,Segoe UI,Roboto; padding:20px;background:#f6f7fb}
.canvas{display:flex;flex-wrap:wrap;gap:16px}
.card{width:320px;background:#fff;border-radius:10px;box-shadow:0 6px 18px rgba(10,10,20,.06);overflow:hidden;border:1px solid #eee}
.card img{width:100%;height:160px;object-fit:cover}
.card h2{margin:12px 16px;font-size:18px}
.card p{margin:0 16px 12px;color:#666}
.btn{background:#0077cc;color:#fff;padding:8px 14px;border:none;border-radius:6px;cursor:pointer}
</style>
</head>
<body>
<div class="canvas">
${clone.innerHTML}
</div>
</body>
</html>`;

  const blob = new Blob([html], { type: 'text/html' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'exported-page.html';
  a.click();
  URL.revokeObjectURL(url);
}