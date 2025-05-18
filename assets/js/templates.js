// File: /var/www/redreporter2/assets/js/templates.js
document.addEventListener('DOMContentLoaded', () => {
  const listEl     = document.getElementById('template-list');
  const loadingEl  = document.getElementById('loading');
  const createBtn  = document.getElementById('create-template');
  const userRole   = document.body.dataset.userRole; // 'admin' or 'consultant'

  let page      = 1;
  const perPage = 20;
  let hasMore   = true;
  let isLoading = false;

  // Coerce to string & escape to prevent XSS
  function escapeHtml(input) {
    return String(input).replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    }[m]));
  }

  // Build a card: header, metadata, hidden preview, and two rows of buttons
  function renderTemplate(t) {
    const isAdmin = userRole === 'admin';
    const card = document.createElement('div');
    card.className = 'template-card' + (t.is_disabled ? ' disabled' : '');
    card.innerHTML = `
      <div class="card-header">
        <h3>${escapeHtml(t.title)}</h3>
        <span class="badge risk-${escapeHtml(t.risk_rating).toLowerCase()}">
          ${escapeHtml(t.risk_rating)}
        </span>
      </div>

      <div class="card-meta">
        <small>By ${escapeHtml(t.created_by)} on ${new Date(t.created_at).toLocaleDateString()}</small>
        <small>Updated by ${escapeHtml(t.updated_by)} on ${new Date(t.updated_at).toLocaleDateString()}</small>
      </div>

      <!-- full Markdown preview; inserted below metadata -->
      <div id="preview-${t.id}" class="template-preview" style="display:none; margin:1rem 0;">
        <h4>Description</h4>
        <div class="md-content">
          ${marked.parse(String(t.description))}
        </div>
        <h4>Remediation</h4>
        <div class="md-content">
          ${marked.parse(String(t.remediation))}
        </div>
      </div>

      <div class="card-actions">
        <div class="actions-row">
          <button data-action="preview" data-id="${t.id}">Preview</button>
          <button data-action="edit"    data-id="${t.id}">Edit</button>
          <button data-action="clone"   data-id="${t.id}">Clone</button>
        </div>
        <div class="actions-row">
          <button data-action="toggle"  data-id="${t.id}" class="btn-disable">
            ${t.is_disabled ? 'Enable' : 'Disable'}
          </button>
          ${isAdmin
            ? `<button data-action="delete" data-id="${t.id}" class="btn-delete">Delete</button>`
            : ''
          }
        </div>
      </div>
    `;
    return card;
  }

  // Fetch & append a page of templates
  function loadTemplates() {
    if (!hasMore || isLoading) return;
    isLoading = true;
    loadingEl.style.display = 'block';

    fetch(`templates_api.php?page=${page}&per_page=${perPage}`)
      .then(res => res.json())
      .then(data => {
        data.templates.forEach(t => {
          listEl.appendChild(renderTemplate(t));
        });
        hasMore = data.has_more;
        page++;
      })
      .catch(err => console.error('Error loading templates:', err))
      .finally(() => {
        isLoading = false;
        loadingEl.style.display = hasMore ? 'block' : 'none';
      });
  }

  // Infinite-scroll trigger
  new IntersectionObserver(entries => {
    if (entries[0].isIntersecting) loadTemplates();
  }, { rootMargin: '200px' }).observe(loadingEl);

  // Delegate all button clicks
  listEl.addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;

    const action = btn.dataset.action;
    const id     = btn.dataset.id;
    const card   = btn.closest('.template-card');
    const pane   = document.getElementById(`preview-${id}`);

    switch (action) {
      case 'preview': {
        const opening = pane.style.display === 'none';
        pane.style.display    = opening ? 'block' : 'none';
        card.style.gridColumn = opening ? '1 / -1' : '';      // full-width
        btn.textContent       = opening ? 'Close' : 'Preview';
        break;
      }
      case 'edit':
        window.location.href = `template_builder.php?id=${id}`;
        break;
      case 'clone':
        window.location.href = `template_builder.php?clone=${id}`;
        break;
      case 'toggle':
        fetch(`templates_api.php?action=toggle&id=${id}`, { method: 'POST' })
          .then(() => window.location.reload());
        break;
      case 'delete':
        if (confirm('Delete this template permanently?')) {
          fetch(`templates_api.php?action=delete&id=${id}`, { method: 'POST' })
            .then(() => window.location.reload());
        }
        break;
    }
  });

  // Create Template button
  createBtn.addEventListener('click', () => {
    window.location.href = 'template_builder.php';
  });

  // Kick off the first load
  loadTemplates();
});
