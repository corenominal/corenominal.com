/* global bootstrap */
(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // State
    // -------------------------------------------------------------------------
    const state = {
        currentTab: 'todo',
        pages: { todo: 1, complete: 1, deleted: 1 },
        dirtyTabs: new Set(['complete', 'deleted', 'categories']),
        categoryFilter: '',
        searchQuery: '',
        changeCatUuid: null,
    };

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    function getCookie(name) {
        const value = '; ' + document.cookie;
        const parts = value.split('; ' + name + '=');
        if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
        return '';
    }

    function apiHeaders() {
        return {
            'Content-Type': 'application/json',
            'apikey': getCookie('apikey'),
            'user-uuid': getCookie('user_uuid'),
        };
    }

    async function apiRequest(method, url, body) {
        const opts = { method: method, headers: apiHeaders() };
        if (body !== undefined) opts.body = JSON.stringify(body);
        const res = await fetch(url, opts);
        const json = await res.json();
        if (!res.ok) {
            throw new Error(json.error || 'Request failed (' + res.status + ')');
        }
        return json;
    }

    function escHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escAttr(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/\n/g, '&#10;')
            .replace(/\r/g, '&#13;');
    }

    /** Add target=_blank and rel=noopener to all <a> tags in an HTML string via DOM parsing */
    function safeHtml(html) {
        const tpl = document.createElement('template');
        tpl.innerHTML = html || '';
        tpl.content.querySelectorAll('a').forEach(function (a) {
            a.setAttribute('target', '_blank');
            a.setAttribute('rel', 'noopener noreferrer');
        });
        return tpl.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(' ', 'T'));
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    function spinner() {
        return '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-secondary" role="status"><span class="visually-hidden">Loading…</span></div></div>';
    }

    // Lightweight canvas confetti implementation
    function _createConfettiCanvas() {
        const c = document.createElement('canvas');
        c.style.position = 'fixed';
        c.style.left = '0';
        c.style.top = '0';
        c.style.width = '100%';
        c.style.height = '100%';
        c.style.pointerEvents = 'none';
        c.style.zIndex = 2000;
        c.width = window.innerWidth;
        c.height = window.innerHeight;
        document.body.appendChild(c);
        return c;
    }

    function _getPrimaryColor() {
        const v = getComputedStyle(document.documentElement).getPropertyValue('--bs-primary') || '';
        return v.trim() || '#0d6efd';
    }

    function launchConfetti(clientX, clientY, opts) {
        // Firework-style radial explosion
        opts = opts || {};
        const canvas = _createConfettiCanvas();
        const ctx = canvas.getContext('2d');
        const shells = [];
        const sparks = [];
        const count = opts.count || 36; // bigger burst
        const primary = _getPrimaryColor();

        const rect = canvas.getBoundingClientRect();
        const originX = clientX - rect.left;
        const originY = clientY - rect.top;

        // Create shell particles that burst outward
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const speed = 4 + Math.random() * 8;
            shells.push({
                x: originX,
                y: originY,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed * 0.9 - (2 + Math.random() * 4),
                life: 30 + Math.floor(Math.random() * 20),
                size: 6 + Math.random() * 6,
                rot: Math.random() * Math.PI,
                vr: (Math.random() - 0.5) * 0.4,
                color: primary,
            });
        }

        // Sparks will be spawned from shells to give sparkling effect
        function spawnSparks(x, y, baseColor) {
            const n = 8 + Math.floor(Math.random() * 8);
            for (let i = 0; i < n; i++) {
                const angle = Math.random() * Math.PI * 2;
                const speed = 1 + Math.random() * 6;
                sparks.push({
                    x: x,
                    y: y,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed - (Math.random() * 2),
                    life: 40 + Math.floor(Math.random() * 40),
                    size: 2 + Math.random() * 3,
                    color: baseColor,
                });
            }
        }

        function drawParticle(p, type) {
            ctx.save();
            ctx.translate(p.x, p.y);
            if (type === 'shell') {
                ctx.rotate(p.rot || 0);
                ctx.fillStyle = p.color;
                ctx.globalAlpha = Math.max(0, Math.min(1, p.life / 60));
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
            } else {
                ctx.beginPath();
                ctx.fillStyle = p.color;
                ctx.globalAlpha = Math.max(0, Math.min(1, p.life / 80));
                ctx.arc(0, 0, p.size, 0, Math.PI * 2);
                ctx.fill();
            }
            ctx.restore();
        }

        function step() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            // update shells
            for (let i = shells.length - 1; i >= 0; i--) {
                const s = shells[i];
                s.vy += 0.12; // gravity
                s.vx *= 0.995; s.vy *= 0.995;
                s.x += s.vx; s.y += s.vy;
                s.rot += s.vr || 0;
                s.life--;

                drawParticle(s, 'shell');

                // when shell expires, spawn sparks
                if (s.life <= 0) {
                    spawnSparks(s.x, s.y, primary);
                    shells.splice(i, 1);
                }
            }

            // update sparks
            for (let i = sparks.length - 1; i >= 0; i--) {
                const p = sparks[i];
                p.vy += 0.08; // lighter gravity for sparks
                p.vx *= 0.994; p.vy *= 0.994;
                p.x += p.vx; p.y += p.vy;
                p.life--;

                drawParticle(p, 'spark');

                if (p.life <= 0 || p.y > canvas.height + 50) {
                    sparks.splice(i, 1);
                }
            }

            if (shells.length > 0 || sparks.length > 0) {
                requestAnimationFrame(step);
            } else {
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
            }
        }

        requestAnimationFrame(step);
    }

    function showToast(message, type) {
        type = type || 'success';
        const toast = document.getElementById('app-toast');
        const body  = document.getElementById('app-toast-body');
        if (!toast || !body) return;
        body.textContent = message;
        toast.className = 'toast align-items-center border-' + type + ' border-1';
        const bsToast = bootstrap.Toast.getOrCreateInstance(toast, { delay: 3500 });
        bsToast.show();
    }

    function removeItemCard(uuid, status) {
        const card = document.querySelector('.todo-item[data-uuid="' + uuid + '"][data-status="' + status + '"]');
        if (!card) return;
        card.style.transition = 'opacity 0.15s ease-out';
        card.style.opacity = '0';
        setTimeout(function () {
            if (!card.parentNode) return;
            card.remove();
            // If the container is now empty, reload to show the empty state (or prev page)
            const container = document.getElementById('items-' + status);
            if (container && container.querySelectorAll('.todo-item').length === 0) {
                loadItems(status, Math.max(1, (state.pages[status] || 1) - 1));
            }
        }, 150);
    }

    function buildUrl(base, params) {
        const q = Object.entries(params)
            .filter(function (e) { return e[1] !== '' && e[1] !== null && e[1] !== undefined; })
            .map(function (e) { return encodeURIComponent(e[0]) + '=' + encodeURIComponent(e[1]); })
            .join('&');
        return q ? base + '?' + q : base;
    }

    // -------------------------------------------------------------------------
    // Markdown textarea shortcuts
    // -------------------------------------------------------------------------
    function toggleInlineMarkup(textarea, marker) {
        const start    = textarea.selectionStart;
        const end      = textarea.selectionEnd;
        const val      = textarea.value;
        const selected = val.substring(start, end);
        const len      = marker.length;
        if (start >= len
                && val.substring(start - len, start) === marker
                && val.substring(end, end + len) === marker) {
            // Markers surround the selection – remove them
            textarea.value = val.substring(0, start - len) + selected + val.substring(end + len);
            textarea.selectionStart = start - len;
            textarea.selectionEnd   = end - len;
        } else if (selected.length >= len * 2
                && selected.startsWith(marker)
                && selected.endsWith(marker)) {
            // Selection includes the markers – remove them
            const inner = selected.substring(len, selected.length - len);
            textarea.value = val.substring(0, start) + inner + val.substring(end);
            textarea.selectionStart = start;
            textarea.selectionEnd   = start + inner.length;
        } else {
            // Wrap with markers
            textarea.value = val.substring(0, start) + marker + selected + marker + val.substring(end);
            if (start === end) {
                // No selection: place cursor between the markers
                textarea.selectionStart = textarea.selectionEnd = start + len;
            } else {
                textarea.selectionStart = start + len;
                textarea.selectionEnd   = end + len;
            }
        }
    }

    function applyTextareaShortcuts(e) {
        const textarea = e.target;
        const start    = textarea.selectionStart;
        const end      = textarea.selectionEnd;
        const val      = textarea.value;

        // ── Bold (Ctrl/⌘+B) ────────────────────────────────────────────────
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'b' || e.key === 'B')) {
            e.preventDefault();
            toggleInlineMarkup(textarea, '**');
            return;
        }

        // ── Italic (Ctrl/⌘+I) ──────────────────────────────────────────────
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'i' || e.key === 'I')) {
            e.preventDefault();
            toggleInlineMarkup(textarea, '*');
            return;
        }

        // ── Backtick ───────────────────────────────────────────────────────
        if (e.key === '`' && !e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey) {
            if (start !== end) {
                // Wrap selection in inline code
                e.preventDefault();
                const selected = val.substring(start, end);
                textarea.value = val.substring(0, start) + '`' + selected + '`' + val.substring(end);
                textarea.selectionStart = start + 1;
                textarea.selectionEnd   = end + 1;
                return;
            }
            // Triple backtick → fenced code block
            const lineStart = val.lastIndexOf('\n', start - 1) + 1;
            if (val.substring(lineStart, start) === '``') {
                e.preventDefault();
                textarea.value = val.substring(0, lineStart) + '```\n\n```' + val.substring(start);
                const cursor = lineStart + 4; // after "```\n"
                textarea.selectionStart = textarea.selectionEnd = cursor;
                return;
            }
        }

        // ── Enter: list / blockquote continuation ──────────────────────────
        if (e.key === 'Enter' && !e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey) {
            const lineStart = val.lastIndexOf('\n', start - 1) + 1;
            const line      = val.substring(lineStart, start);

            // Blockquote
            const bqMatch = line.match(/^(>\s?)/);
            if (bqMatch) {
                e.preventDefault();
                const prefix = bqMatch[1];
                if (line.substring(prefix.length).trim() === '') {
                    // Empty blockquote line – exit the structure
                    textarea.value = val.substring(0, lineStart) + '\n' + val.substring(start);
                    textarea.selectionStart = textarea.selectionEnd = lineStart + 1;
                } else {
                    // Continue blockquote
                    textarea.value = val.substring(0, start) + '\n' + prefix + val.substring(start);
                    textarea.selectionStart = textarea.selectionEnd = start + 1 + prefix.length;
                }
                return;
            }

            // Unordered list
            const ulMatch = line.match(/^(\s*[-*+] )/);
            if (ulMatch) {
                e.preventDefault();
                const prefix = ulMatch[1];
                if (line.substring(prefix.length).trim() === '') {
                    // Empty list item – exit the list
                    textarea.value = val.substring(0, lineStart) + '\n' + val.substring(start);
                    textarea.selectionStart = textarea.selectionEnd = lineStart + 1;
                } else {
                    // Continue list
                    textarea.value = val.substring(0, start) + '\n' + prefix + val.substring(start);
                    textarea.selectionStart = textarea.selectionEnd = start + 1 + prefix.length;
                }
                return;
            }

            // Ordered list
            const olMatch = line.match(/^(\s*)(\d+)([.)]) /);
            if (olMatch) {
                e.preventDefault();
                const indent     = olMatch[1];
                const num        = parseInt(olMatch[2], 10);
                const sep        = olMatch[3];
                const fullPrefix = olMatch[0];
                if (line.substring(fullPrefix.length).trim() === '') {
                    // Empty ordered item – exit the list
                    textarea.value = val.substring(0, lineStart) + '\n' + val.substring(start);
                    textarea.selectionStart = textarea.selectionEnd = lineStart + 1;
                } else {
                    // Continue with incremented number
                    const newPrefix = indent + (num + 1) + sep + ' ';
                    textarea.value = val.substring(0, start) + '\n' + newPrefix + val.substring(start);
                    textarea.selectionStart = textarea.selectionEnd = start + 1 + newPrefix.length;
                }
                return;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Filter bar
    // -------------------------------------------------------------------------
    function updateFilterBar() {
        const bar      = document.getElementById('filter-bar');
        const barText  = document.getElementById('filter-bar-text');
        const parts    = [];

        if (state.searchQuery) {
            parts.push('Search: <strong>' + escHtml(state.searchQuery) + '</strong>');
        }
        if (state.categoryFilter) {
            parts.push('Category: <strong>' + escHtml(state.categoryFilter) + '</strong>');
        }

        if (parts.length > 0) {
            barText.innerHTML = parts.join(' &nbsp;|&nbsp; ');
            bar.classList.remove('d-none');
            bar.classList.add('d-flex');
        } else {
            bar.classList.add('d-none');
            bar.classList.remove('d-flex');
        }

        const clearSearchBtn = document.getElementById('clear-search-btn');
        const searchBtn      = document.getElementById('search-btn');
        if (state.searchQuery) {
            clearSearchBtn.classList.remove('d-none');
            searchBtn.classList.add('d-none');
        } else {
            clearSearchBtn.classList.add('d-none');
            searchBtn.classList.remove('d-none');
        }
    }

    // -------------------------------------------------------------------------
    // Load counts
    // -------------------------------------------------------------------------
    async function loadCounts() {
        try {
            const url  = buildUrl('/api/todo/counts', {
                search: state.searchQuery,
                category: state.categoryFilter,
            });
            const data = await apiRequest('GET', url);
            document.getElementById('tab-todo-count').textContent    = data.todo;
            document.getElementById('tab-complete-count').textContent = data.complete;
            document.getElementById('tab-deleted-count').textContent  = data.deleted;
        } catch (e) {
            // Non-fatal – counts will just be stale
        }
    }

    // -------------------------------------------------------------------------
    // Load categories (datalist)
    // -------------------------------------------------------------------------
    async function loadCategories() {
        try {
            const data = await apiRequest('GET', '/api/todo/categories');
            const list = document.getElementById('category-datalist');
            if (!list) return;
            list.innerHTML = '';
            (data.categories || []).forEach(function (cat) {
                const opt = document.createElement('option');
                opt.value = cat.name;
                list.appendChild(opt);
            });
        } catch (e) {
            // Non-fatal
        }
    }

    // -------------------------------------------------------------------------
    // Load / render categories pane
    // -------------------------------------------------------------------------
    async function loadCategoriesPane() {
        const container = document.getElementById('items-categories');
        if (!container) return;

        container.innerHTML = spinner();
        try {
            const data = await apiRequest('GET', '/api/todo/categories');
            renderCategoriesPane(container, data.categories || []);
        } catch (e) {
            container.innerHTML = '<p class="text-danger py-3"><i class="bi bi-exclamation-triangle me-1"></i>' + escHtml(e.message) + '</p>';
        }
    }

    function renderCategoriesPane(container, categories) {
        if (categories.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No categories yet.</p>';
            return;
        }

        let html = '<div class="row g-2 pt-1">';
        categories.forEach(function (cat) {
            html += '<div class="col-12 col-sm-6 col-md-4 col-lg-3">';
            html += '<div class="card h-100 clickable-category-card" role="button" tabindex="0"'
                + ' data-category="' + escAttr(cat.name) + '">';
            html += '<div class="card-body py-2 px-3">';
            html += '<div class="fw-semibold mb-1">' + escHtml(cat.name) + '</div>';
            html += '<div class="d-flex gap-1 flex-wrap">';
            if (cat.todo > 0) {
                html += '<span class="badge bg-primary">' + cat.todo + ' todo</span>';
            }
            if (cat.complete > 0) {
                html += '<span class="badge bg-primary">' + cat.complete + ' completed</span>';
            }
            html += '</div>';
            html += '</div></div></div>';
        });
        html += '</div>';

        container.innerHTML = html;
    }

    async function refreshCategories() {
        await loadCategories();
        if (state.currentTab === 'categories') {
            await loadCategoriesPane();
        } else {
            state.dirtyTabs.add('categories');
        }
    }

    // -------------------------------------------------------------------------
    // Load items for a tab
    // -------------------------------------------------------------------------
    async function loadItems(status, page) {
        page = page || 1;
        state.pages[status] = page;

        const container  = document.getElementById('items-' + status);
        const pagEl      = document.getElementById('pagination-' + status);
        if (!container) return;

        container.innerHTML = spinner();
        if (pagEl) pagEl.innerHTML = '';

        try {
            const url  = buildUrl('/api/todo/items', {
                status: status,
                page: page,
                search: state.searchQuery,
                category: state.categoryFilter,
            });
            const data = await apiRequest('GET', url);
            renderItems(status, container, pagEl, data);
        } catch (e) {
            container.innerHTML = '<p class="text-danger py-3"><i class="bi bi-exclamation-triangle me-1"></i>' + escHtml(e.message) + '</p>';
        }
    }

    // -------------------------------------------------------------------------
    // Render items
    // -------------------------------------------------------------------------
    function renderItems(status, container, pagEl, data) {
        if (!data.items || data.items.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-4">No items found.</p>';
            return;
        }
        container.innerHTML = data.items.map(function (item) {
            return renderItem(item, status);
        }).join('');

        if (pagEl) renderPagination(pagEl, status, data.total, data.page, data.perPage);
    }

    function renderItem(item, status) {
        const isPinned    = parseInt(item.is_pinned, 10) === 1;
        const cardBorder  = isPinned ? ' todo-pinned' : '';
        const createdDate = formatDate(item.created_at);
        const html        = safeHtml(item.html || '');

        let out = '<div class="todo-item card mb-2' + cardBorder + '"'
            + ' data-uuid="' + escAttr(item.uuid) + '"'
            + ' data-status="' + escAttr(status) + '"'
            + ' data-markdown="' + escAttr(item.markdown) + '"'
            + ' data-category="' + escAttr(item.category) + '"'
            + ' data-pinned="' + (isPinned ? '1' : '0') + '"'
            + '>';

        out += '<div class="card-body p-3">';

        // ── Content area ──────────────────────────────────────────────────────
        if (status === 'todo') {
            out += '<div class="todo-content" role="button" title="Click to edit" aria-label="Click to edit this item">' + html + '</div>';
            out += '<div class="todo-edit-area d-none">';
            out += '<textarea class="form-control font-monospace mb-2 edit-textarea" rows="5">' + escHtml(item.markdown) + '</textarea>';
            out += '<div class="input-group input-group-sm mb-2">';
            out += '<span class="input-group-text"><i class="bi bi-tag"></i></span>';
            out += '<input type="text" class="form-control edit-category" value="' + escAttr(item.category) + '" list="category-datalist" autocomplete="off" placeholder="Category">';
            out += '</div>';
            out += '<div class="d-flex gap-2">';
            out += '<button class="btn btn-primary btn-sm save-edit-btn"><i class="bi bi-check-lg me-1"></i>Save</button>';
            out += '<button class="btn btn-secondary btn-sm cancel-edit-btn">Cancel</button>';
            out += '</div>';
            out += '</div>'; // .todo-edit-area
        } else {
            out += '<div class="todo-content">' + html + '</div>';
        }

        // ── Meta row ──────────────────────────────────────────────────────────
        out += '<div class="d-flex flex-wrap gap-2 align-items-center mt-2 small">';
        out += '<span class="text-muted"><i class="bi bi-calendar3 me-1"></i>' + escHtml(createdDate) + '</span>';
        if (status === 'complete' && item.completed_at) {
            out += '<span class="text-muted"><i class="bi bi-check-circle me-1"></i>Completed ' + escHtml(formatDate(item.completed_at)) + '</span>';
        }
        if (isPinned) {
            out += '<span class="text-primary"><i class="bi bi-pin-angle-fill me-1"></i>Pinned</span>';
        }
        out += '<span class="badge rounded-pill bg-primary todo-category-badge clickable-category"'
            + ' role="button" tabindex="0" title="Filter by this category"'
            + ' data-category="' + escAttr(item.category) + '">'
            + escHtml(item.category) + '</span>';
        out += '</div>';

        // ── Action buttons ────────────────────────────────────────────────────
        out += '<div class="d-flex flex-wrap gap-1 mt-2 todo-actions">';

        if (status === 'todo') {
            out += '<button class="btn btn-sm btn-outline-primary pin-btn" data-uuid="' + escAttr(item.uuid) + '" title="' + (isPinned ? 'Unpin' : 'Pin') + '">'
                + '<i class="bi bi-pin-angle' + (isPinned ? '-fill' : '') + ' me-1"></i>' + (isPinned ? 'Unpin' : 'Pin')
                + '</button>';
            out += '<button class="btn btn-sm btn-outline-primary done-btn" data-uuid="' + escAttr(item.uuid) + '" title="Mark as done">'
                + '<i class="bi bi-check-lg me-1"></i>Done</button>';
            out += '<button class="btn btn-sm btn-outline-primary change-cat-btn" data-uuid="' + escAttr(item.uuid) + '" data-category="' + escAttr(item.category) + '" title="Change category">'
                + '<i class="bi bi-tag me-1"></i>Category</button>';
            out += '<button class="btn btn-sm btn-outline-primary delete-btn" data-uuid="' + escAttr(item.uuid) + '" title="Delete">'
                + '<i class="bi bi-trash me-1"></i>Delete</button>';
        } else if (status === 'complete') {
            out += '<button class="btn btn-sm btn-outline-primary undo-btn" data-uuid="' + escAttr(item.uuid) + '" title="Mark as not complete">'
                + '<i class="bi bi-arrow-counterclockwise me-1"></i>Undo</button>';
            out += '<button class="btn btn-sm btn-outline-primary delete-btn" data-uuid="' + escAttr(item.uuid) + '" title="Delete">'
                + '<i class="bi bi-trash me-1"></i>Delete</button>';
        } else if (status === 'deleted') {
            out += '<button class="btn btn-sm btn-outline-primary restore-btn" data-uuid="' + escAttr(item.uuid) + '" title="Restore">'
                + '<i class="bi bi-arrow-clockwise me-1"></i>Restore</button>';
            out += '<button class="btn btn-sm btn-outline-primary destroy-btn" data-uuid="' + escAttr(item.uuid) + '" title="Permanently delete">'
                + '<i class="bi bi-trash3 me-1"></i>Delete permanently</button>';
        }

        out += '</div>'; // .todo-actions
        out += '</div>'; // .card-body
        out += '</div>'; // .todo-item

        return out;
    }

    // -------------------------------------------------------------------------
    // Pagination
    // -------------------------------------------------------------------------
    function renderPagination(pagEl, status, total, page, perPage) {
        const totalPages = Math.ceil(total / perPage);
        if (totalPages <= 1) return;

        let html = '<nav aria-label="Pagination"><ul class="pagination pagination-sm mb-0 flex-wrap">';

        // Prev
        html += '<li class="page-item' + (page === 1 ? ' disabled' : '') + '">'
            + '<button class="page-link page-btn" data-status="' + escAttr(status) + '" data-page="' + (page - 1) + '">&laquo;</button></li>';

        // Pages – show a window around current
        const delta = 2;
        let start = Math.max(1, page - delta);
        let end   = Math.min(totalPages, page + delta);
        if (start > 1) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';

        for (let i = start; i <= end; i++) {
            html += '<li class="page-item' + (i === page ? ' active' : '') + '">'
                + '<button class="page-link page-btn" data-status="' + escAttr(status) + '" data-page="' + i + '">' + i + '</button></li>';
        }

        if (end < totalPages) html += '<li class="page-item disabled"><span class="page-link">…</span></li>';

        // Next
        html += '<li class="page-item' + (page === totalPages ? ' disabled' : '') + '">'
            + '<button class="page-link page-btn" data-status="' + escAttr(status) + '" data-page="' + (page + 1) + '">&raquo;</button></li>';

        html += '</ul></nav>';
        pagEl.innerHTML = html;
    }

    // -------------------------------------------------------------------------
    // Inline edit helpers
    // -------------------------------------------------------------------------
    function enterEditMode(card) {
        const content  = card.querySelector('.todo-content');
        const editArea = card.querySelector('.todo-edit-area');
        const textarea = card.querySelector('.edit-textarea');
        const catInput = card.querySelector('.edit-category');
        if (!content || !editArea) return;

        // populate from data attributes (browser auto-decodes HTML entities)
        textarea.value = card.dataset.markdown || '';
        catInput.value = card.dataset.category || '';

        content.classList.add('d-none');
        editArea.classList.remove('d-none');
        textarea.focus();
    }

    function exitEditMode(card) {
        const content  = card.querySelector('.todo-content');
        const editArea = card.querySelector('.todo-edit-area');
        if (!content || !editArea) return;
        content.classList.remove('d-none');
        editArea.classList.add('d-none');
    }

    // -------------------------------------------------------------------------
    // Refresh helpers
    // -------------------------------------------------------------------------
    async function refreshCurrentTab() {
        await Promise.all([
            loadCounts(),
            loadItems(state.currentTab, state.pages[state.currentTab]),
        ]);
    }

    function markDirty(...tabs) {
        tabs.forEach(function (t) {
            if (t !== state.currentTab) state.dirtyTabs.add(t);
        });
    }

    // -------------------------------------------------------------------------
    // Event listeners
    // -------------------------------------------------------------------------
    function setupEvents() {

        // ── Create form ───────────────────────────────────────────────────────
        document.getElementById('create-todo-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const markdownEl = document.getElementById('todo-markdown');
            const categoryEl = document.getElementById('todo-category');
            const markdown   = markdownEl.value.trim();
            const category   = categoryEl.value.trim();

            if (!markdown) {
                showToast('Please write something first.', 'warning');
                markdownEl.focus();
                return;
            }

            const btn = document.getElementById('create-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Creating…';
            try {
                await apiRequest('POST', '/api/todo/items', { markdown: markdown, category: category });
                markdownEl.value = '';
                categoryEl.value = '';
                markdownEl.focus();
                showToast('Item created.');
                // If not on todo tab, go there
                if (state.currentTab !== 'todo') {
                    state.dirtyTabs.add('categories');
                    document.getElementById('tab-todo-btn').click();
                } else {
                    state.pages.todo = 1;
                    await refreshCurrentTab();
                    await refreshCategories();
                }
            } catch (err) {
                showToast(err.message, 'danger');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Create';
            }
        });

        // ── Markdown shortcuts (create textarea) ─────────────────────────────
        document.getElementById('todo-markdown').addEventListener('keydown', applyTextareaShortcuts);

        // ── Save shortcut (create form, any field) ────────────────────────────
        document.getElementById('create-todo-form').addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                this.requestSubmit();
            }
        });

        // ── Tab switching ────────────────────────────────────────────────────
        document.getElementById('todo-tabs').addEventListener('shown.bs.tab', async function (e) {
            const tab = e.target.dataset.tab;
            if (!tab) return;
            state.currentTab = tab;
            if (state.dirtyTabs.has(tab)) {
                state.dirtyTabs.delete(tab);
                if (tab === 'categories') {
                    await loadCategoriesPane();
                } else {
                    state.pages[tab] = 1;
                    await loadItems(tab, 1);
                }
            }
        });

        // ── Search ────────────────────────────────────────────────────────────
        function executeSearch() {
            const val = document.getElementById('search-input').value.trim();
            state.searchQuery = val;
            state.pages       = { todo: 1, complete: 1, deleted: 1 };
            updateFilterBar();
            Promise.all([loadCounts(), loadItems(state.currentTab, 1)]);
            // Mark other tabs dirty so they reload on switch
            markDirty('todo', 'complete', 'deleted');
        }

        document.getElementById('search-btn').addEventListener('click', executeSearch);

        document.getElementById('search-input').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); executeSearch(); }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'f' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                const searchInput = document.getElementById('search-input');
                searchInput.focus();
                searchInput.select();
            }
        });

        document.getElementById('clear-search-btn').addEventListener('click', function () {
            document.getElementById('search-input').value = '';
            state.searchQuery = '';
            state.pages = { todo: 1, complete: 1, deleted: 1 };
            updateFilterBar();
            Promise.all([loadCounts(), loadItems(state.currentTab, 1)]);
            markDirty('todo', 'complete', 'deleted');
        });

        // ── Filter bar: clear all ─────────────────────────────────────────────
        document.getElementById('clear-filters-btn').addEventListener('click', function () {
            document.getElementById('search-input').value = '';
            state.searchQuery    = '';
            state.categoryFilter = '';
            state.pages          = { todo: 1, complete: 1, deleted: 1 };
            updateFilterBar();
            Promise.all([loadCounts(), loadItems(state.currentTab, 1)]);
            markDirty('todo', 'complete', 'deleted');
        });

        // ── Delegated events on tab content ───────────────────────────────────
        ['todo', 'complete', 'deleted'].forEach(function (status) {
            const pane = document.getElementById('pane-' + status);
            if (!pane) return;

            pane.addEventListener('click', async function (e) {

                // ── Pagination ─────────────────────────────────────────────
                const pageBtn = e.target.closest('.page-btn');
                if (pageBtn) {
                    const s = pageBtn.dataset.status;
                    const p = parseInt(pageBtn.dataset.page, 10);
                    await loadItems(s, p);
                    // Scroll tab content into view
                    const tc = document.getElementById('todo-tabs-content');
                    if (tc) tc.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    return;
                }

                // ── Category filter badge ───────────────────────────────────
                const catBadge = e.target.closest('.clickable-category');
                if (catBadge) {
                    const cat = catBadge.dataset.category;
                    state.categoryFilter = cat;
                    state.pages = { todo: 1, complete: 1, deleted: 1 };
                    updateFilterBar();
                    Promise.all([loadCounts(), loadItems(state.currentTab, 1)]);
                    markDirty('todo', 'complete', 'deleted');
                    return;
                }

                // -- Pin button ─────────────────────────────────────────────
                const pinBtn = e.target.closest('.pin-btn');
                if (pinBtn) {
                    pinBtn.disabled = true;
                    try {
                        await apiRequest('POST', '/api/todo/items/' + pinBtn.dataset.uuid + '/pin');
                        // Toggle pin state in-place without re-rendering the list
                        const card = pinBtn.closest('.todo-item');
                        const nowPinned = card.dataset.pinned !== '1';
                        card.dataset.pinned = nowPinned ? '1' : '0';
                        card.classList.toggle('todo-pinned', nowPinned);
                        pinBtn.title = nowPinned ? 'Unpin' : 'Pin';
                        pinBtn.innerHTML = '<i class="bi bi-pin-angle' + (nowPinned ? '-fill' : '') + ' me-1"></i>' + (nowPinned ? 'Unpin' : 'Pin');
                        // Toggle the "Pinned" badge in the meta row
                        const metaRow = card.querySelector('.d-flex.flex-wrap.gap-2');
                        if (metaRow) {
                            const existingBadge = metaRow.querySelector('.bi-pin-angle-fill');
                            if (nowPinned && !existingBadge) {
                                const badge = document.createElement('span');
                                badge.className = 'text-primary';
                                badge.innerHTML = '<i class="bi bi-pin-angle-fill me-1"></i>Pinned';
                                metaRow.insertBefore(badge, metaRow.querySelector('.todo-category-badge'));
                            } else if (!nowPinned && existingBadge) {
                                existingBadge.closest('span.text-primary').remove();
                            }
                        }
                        // Reorder card in DOM, preserving scroll position
                        const savedScrollY = window.scrollY;
                        const container = card.parentNode;
                        if (container) {
                            if (nowPinned) {
                                // Move to before the first non-pinned card (i.e. top of the pinned group)
                                const firstUnpinned = container.querySelector('.todo-item:not([data-pinned="1"])');
                                container.insertBefore(card, firstUnpinned || container.firstChild);
                            } else {
                                // Move to after the last pinned card
                                const pinnedCards = container.querySelectorAll('.todo-item[data-pinned="1"]');
                                const lastPinned = pinnedCards.length ? pinnedCards[pinnedCards.length - 1] : null;
                                container.insertBefore(card, lastPinned ? lastPinned.nextSibling : container.firstChild);
                            }
                        }
                        window.scrollTo({ top: savedScrollY, behavior: 'instant' });
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        pinBtn.disabled = false;
                    }
                    return;
                }

                // ── Done button ─────────────────────────────────────────────
                const doneBtn = e.target.closest('.done-btn');
                if (doneBtn) {
                    const clickX = e.clientX;
                    const clickY = e.clientY;
                    doneBtn.disabled = true;
                    try {
                        await apiRequest('POST', '/api/todo/items/' + doneBtn.dataset.uuid + '/status', { status: 'complete' });
                        showToast('Marked as done!');
                        try { launchConfetti(clickX, clickY); } catch (err) { /* ignore confetti errors */ }
                        markDirty('complete');
                        removeItemCard(doneBtn.dataset.uuid, 'todo');
                        loadCounts();
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        doneBtn.disabled = false;
                    }
                    return;
                }

                // ── Undo button (complete → todo) ───────────────────────────
                const undoBtn = e.target.closest('.undo-btn');
                if (undoBtn) {
                    undoBtn.disabled = true;
                    try {
                        await apiRequest('POST', '/api/todo/items/' + undoBtn.dataset.uuid + '/status', { status: 'todo' });
                        showToast('Moved back to TODO.');
                        markDirty('todo');
                        removeItemCard(undoBtn.dataset.uuid, 'complete');
                        loadCounts();
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        undoBtn.disabled = false;
                    }
                    return;
                }

                // ── Change category button ──────────────────────────────────
                const changeCatBtn = e.target.closest('.change-cat-btn');
                if (changeCatBtn) {
                    state.changeCatUuid = changeCatBtn.dataset.uuid;
                    document.getElementById('modal-category-input').value = changeCatBtn.dataset.category || '';
                    const modal = new bootstrap.Modal(document.getElementById('changeCategoryModal'));
                    modal.show();
                    return;
                }

                // ── Delete (soft) ───────────────────────────────────────────
                const deleteBtn = e.target.closest('.delete-btn');
                if (deleteBtn) {
                    deleteBtn.disabled = true;
                    try {
                        await apiRequest('POST', '/api/todo/items/' + deleteBtn.dataset.uuid + '/delete');
                        showToast('Item deleted.');
                        markDirty('deleted');
                        removeItemCard(deleteBtn.dataset.uuid, status);
                        loadCounts();
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        deleteBtn.disabled = false;
                    }
                    return;
                }

                // ── Restore ─────────────────────────────────────────────────
                const restoreBtn = e.target.closest('.restore-btn');
                if (restoreBtn) {
                    restoreBtn.disabled = true;
                    try {
                        await apiRequest('POST', '/api/todo/items/' + restoreBtn.dataset.uuid + '/restore');
                        showToast('Item restored.');
                        markDirty('todo');
                        removeItemCard(restoreBtn.dataset.uuid, 'deleted');
                        loadCounts();
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        restoreBtn.disabled = false;
                    }
                    return;
                }

                // ── Destroy (permanent delete) ──────────────────────────────
                const destroyBtn = e.target.closest('.destroy-btn');
                if (destroyBtn) {
                    destroyBtn.disabled = true;
                    try {
                        await apiRequest('POST', '/api/todo/items/' + destroyBtn.dataset.uuid + '/destroy');
                        showToast('Item permanently deleted.', 'warning');
                        markDirty('categories');
                        removeItemCard(destroyBtn.dataset.uuid, 'deleted');
                        loadCounts();
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        destroyBtn.disabled = false;
                    }
                    return;
                }

                // ── Inline save edit ────────────────────────────────────────
                const saveBtn = e.target.closest('.save-edit-btn');
                if (saveBtn) {
                    const card     = saveBtn.closest('.todo-item');
                    const textarea = card.querySelector('.edit-textarea');
                    const catInput = card.querySelector('.edit-category');
                    const markdown = textarea.value.trim();
                    if (!markdown) {
                        showToast('Content cannot be empty.', 'warning');
                        textarea.focus();
                        return;
                    }
                    saveBtn.disabled = true;
                    try {
                        const updated = await apiRequest('POST', '/api/todo/items/' + card.dataset.uuid, {
                            markdown: markdown,
                            category: catInput.value.trim(),
                        });
                        showToast('Item updated.');
                        // Update card data attributes and re-render content in place
                        card.dataset.markdown = updated.item.markdown;
                        card.dataset.category = updated.item.category;
                        card.querySelector('.todo-content').innerHTML = safeHtml(updated.item.html || '');
                        card.querySelector('.todo-category-badge').textContent = updated.item.category;
                        card.querySelector('.todo-category-badge').dataset.category = updated.item.category;
                        exitEditMode(card);
                        await refreshCategories();
                        await loadCounts();
                    } catch (err) {
                        showToast(err.message, 'danger');
                    } finally {
                        saveBtn.disabled = false;
                    }
                    return;
                }

                // ── Inline cancel edit ──────────────────────────────────────
                const cancelBtn = e.target.closest('.cancel-edit-btn');
                if (cancelBtn) {
                    exitEditMode(cancelBtn.closest('.todo-item'));
                    return;
                }

                // ── Click on todo content → enter edit mode ─────────────────
                if (status !== 'todo') return;
                const contentDiv = e.target.closest('.todo-content');
                if (!contentDiv) return;
                // Don't edit when clicking a real link
                if (e.target.closest('a')) return;
                const card = contentDiv.closest('.todo-item');
                if (!card) return;
                // Don't re-enter if already editing
                if (!card.querySelector('.todo-edit-area').classList.contains('d-none')) return;
                enterEditMode(card);
            });

            pane.addEventListener('keydown', function (e) {
                if (e.target.classList.contains('edit-textarea')) applyTextareaShortcuts(e);
                // Save shortcut works from any field within an edit area
                if ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 's' || e.key === 'S')) {
                    const editArea = e.target.closest('.todo-edit-area');
                    if (editArea) {
                        e.preventDefault();
                        const saveBtn = editArea.querySelector('.save-edit-btn');
                        if (saveBtn) saveBtn.click();
                    }
                }
            });
        });

        // ── Categories pane ───────────────────────────────────────────────────
        const catPane = document.getElementById('pane-categories');
        if (catPane) {
            catPane.addEventListener('click', function (e) {
                const card = e.target.closest('.clickable-category-card');
                if (!card) return;
                state.categoryFilter = card.dataset.category;
                state.pages = { todo: 1, complete: 1, deleted: 1 };
                updateFilterBar();
                loadCounts();
                markDirty('todo', 'complete', 'deleted');
                document.getElementById('tab-todo-btn').click();
            });
            catPane.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    const card = e.target.closest('.clickable-category-card');
                    if (card) { e.preventDefault(); card.click(); }
                }
            });
        }

        // ── Change category modal: save ───────────────────────────────────────
        document.getElementById('save-category-modal-btn').addEventListener('click', async function () {
            if (!state.changeCatUuid) return;
            const catInput = document.getElementById('modal-category-input');
            const category = catInput.value.trim().toLowerCase() || 'uncategorised';
            const uuid = state.changeCatUuid;
            this.disabled = true;
            try {
                await apiRequest('POST', '/api/todo/items/' + uuid, { category: category });
                bootstrap.Modal.getInstance(document.getElementById('changeCategoryModal')).hide();
                showToast('Category updated.');
                // Update the card in place without re-rendering the list
                const card = document.querySelector('.todo-item[data-uuid="' + uuid + '"]');
                if (card) {
                    card.dataset.category = category;
                    const badge = card.querySelector('.todo-category-badge');
                    if (badge) { badge.textContent = category; badge.dataset.category = category; }
                    const catInput2 = card.querySelector('.edit-category');
                    if (catInput2) catInput2.value = category;
                }
                await Promise.all([loadCounts(), refreshCategories()]);
            } catch (err) {
                showToast(err.message, 'danger');
            } finally {
                this.disabled = false;
                state.changeCatUuid = null;
            }
        });

        // Close modal → reset UUID
        document.getElementById('changeCategoryModal').addEventListener('hidden.bs.modal', function () {
            state.changeCatUuid = null;
        });
    }

    // -------------------------------------------------------------------------
    // Init
    // -------------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', async function () {
        setupEvents();
        updateFilterBar();
        await Promise.all([loadCounts(), loadItems('todo', 1), loadCategories()]);
        document.getElementById('todo-markdown').focus();
    });

}());
