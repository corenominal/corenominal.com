'use strict';

// ===== STATE =====
let currentSessionUuid = null;
let isStreaming         = false;
let userScrolledUp     = false;
let pendingImages      = []; // [{dataUrl, base64, name}]
let pendingDocuments   = []; // [{name, type, content}]
let lastSentMessage    = '';
let lastSentImages     = [];
let lastSentDocuments  = [];
let selectedModel      = '';
let selectedProvider   = 'ollama'; // 'ollama' | 'openrouter'
const modelCache       = {}; // { ollama: [...], openrouter: [...] }

// ===== DOM REFS =====
let chatThread, welcomeScreen;
let messageInput, sendBtn, chatList, modelBtn, modelList, newChatBtn, searchBtn;
let searchModal, searchInput, searchResults;
let attachBtn, imageInput, imagePreviewArea;
let searchDebounceTimer = null;

// ===== AUTH =====
let masterKey = '';

// ===== INIT =====
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('chat-container');
    masterKey       = container?.dataset.masterKey || '';

    chatThread    = document.getElementById('chat-thread');
    welcomeScreen = document.getElementById('welcome-screen');
    messageInput  = document.getElementById('message-input');
    sendBtn       = document.getElementById('send-btn');
    chatList      = document.getElementById('chat-list');
    modelBtn      = document.getElementById('model-btn');
    modelList     = document.getElementById('model-list');
    newChatBtn    = document.getElementById('new-chat-btn');
    searchBtn     = document.getElementById('search-btn');
    searchInput   = document.getElementById('search-input');
    searchResults = document.getElementById('search-results');
    searchModal   = bootstrap.Modal.getOrCreateInstance(document.getElementById('searchModal'));

    attachBtn        = document.getElementById('attach-btn');
    imageInput       = document.getElementById('image-input');
    imagePreviewArea = document.getElementById('image-preview-area');

    messageInput.addEventListener('input', onInputChange);
    messageInput.addEventListener('keydown', onKeyDown);
    sendBtn.addEventListener('click', sendMessage);
    newChatBtn.addEventListener('click', startNewChat);
    searchBtn.addEventListener('click', openSearchModal);
    attachBtn.addEventListener('click', () => imageInput.click());
    imageInput.addEventListener('change', handleFileSelect);

    document.getElementById('searchModal').addEventListener('shown.bs.modal', () => searchInput.focus());
    document.getElementById('searchModal').addEventListener('hidden.bs.modal', () => {
        searchInput.value = '';
        searchResults.innerHTML = '<p class="text-secondary text-center small py-4 mb-0">Type to search…</p>';
        clearTimeout(searchDebounceTimer);
    });
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            searchResults.innerHTML = '<p class="text-secondary text-center small py-4 mb-0">Type to search…</p>';
            return;
        }
        searchResults.innerHTML = '<p class="text-secondary text-center small py-4 mb-0"><span class="spinner-border spinner-border-sm me-2"></span>Searching…</p>';
        searchDebounceTimer = setTimeout(() => performSearch(q), 300);
    });

    document.addEventListener('keydown', e => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            openSearchModal();
        }
    });

    // Track user scroll to avoid auto-scrolling when they've scrolled up
    window.addEventListener('scroll', () => {
        const threshold = 80;
        userScrolledUp = (window.innerHeight + window.scrollY) < (document.body.scrollHeight - threshold);
    });

    // Restore provider preference
    selectedProvider = localStorage.getItem('chat_provider') || 'ollama';
    setActiveProviderTab(selectedProvider, false);

    // Wire up provider tabs
    document.querySelectorAll('#provider-tabs [data-provider]').forEach(btn => {
        btn.addEventListener('click', () => {
            const prov = btn.dataset.provider;
            setActiveProviderTab(prov, true);
        });
    });

    // Open model modal: switch to the tab for the current provider
    document.getElementById('modelModal').addEventListener('show.bs.modal', () => {
        setActiveProviderTab(selectedProvider, true);
    });

    loadModels(selectedProvider).then(() => {
        const initialUuid = container?.dataset.sessionUuid;
        if (initialUuid) {
            loadSession(initialUuid);
        }
    });

    loadSessions();
});

// ===== MODELS =====
function setActiveProviderTab(provider, loadIfNeeded = true) {
    selectedProvider = provider;
    localStorage.setItem('chat_provider', provider);

    document.querySelectorAll('#provider-tabs [data-provider]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.provider === provider);
    });

    if (loadIfNeeded) {
        if (modelCache[provider]) {
            renderModelList(modelCache[provider], provider);
        } else {
            loadModels(provider);
        }
    }
}

async function loadModels(provider = 'ollama') {
    modelList.innerHTML = '<p class="text-secondary text-center small py-4 mb-0">Loading models…</p>';
    try {
        const res    = await fetch('/api/ai/chat/models?provider=' + encodeURIComponent(provider), {
            headers: { apikey: masterKey },
        });
        const data   = await res.json();
        const models = data.models || [];
        modelCache[provider] = models;
        renderModelList(models, provider);

        // Set initial selection from localStorage or first available
        const savedProvider = localStorage.getItem('chat_provider') || 'ollama';
        if (provider === savedProvider && !selectedModel) {
            const saved = localStorage.getItem('chat_model');
            setSelectedModel(saved && models.includes(saved) ? saved : (models[0] || null), provider);
        }
    } catch (e) {
        modelList.innerHTML = '<p class="text-danger text-center small py-4 mb-0">Failed to load models.</p>';
        document.getElementById('model-btn-label').textContent = 'Unavailable';
    }
}

function renderModelList(models, provider) {
    if (models.length === 0) {
        const msg = provider === 'openrouter'
            ? 'No models enabled. <a href="/admin/ai/openrouter-models">Configure OpenRouter models</a>.'
            : 'No models found.';
        modelList.innerHTML = `<p class="text-secondary text-center small py-4 mb-0">${msg}</p>`;
        return;
    }

    const frag = document.createDocumentFragment();
    models.forEach(name => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'model-list-item d-flex align-items-center justify-content-between w-100 px-3 py-3 border-bottom bg-transparent border-start-0 border-end-0 border-top-0 text-start';
        item.dataset.model = name;
        const isActive = name === selectedModel && provider === selectedProvider;
        item.innerHTML = `<span>${escHtml(name)}</span><i class="bi bi-check-circle-fill"${isActive ? '' : ' hidden'}></i>`;
        item.addEventListener('click', () => {
            setSelectedModel(name, provider);
            bootstrap.Modal.getInstance(document.getElementById('modelModal')).hide();
        });
        frag.appendChild(item);
    });

    modelList.innerHTML = '';
    modelList.appendChild(frag);
}

function setSelectedModel(name, provider = selectedProvider) {
    selectedModel    = name;
    selectedProvider = provider;
    localStorage.setItem('chat_model', name || '');
    localStorage.setItem('chat_provider', provider);

    const providerLabel = provider === 'openrouter' ? 'OR' : 'Ollama';
    document.getElementById('model-btn-label').textContent = name
        ? `${providerLabel} / ${name}`
        : 'Select model…';

    document.querySelectorAll('#model-list [data-model]').forEach(item => {
        const check = item.querySelector('.bi-check-circle-fill');
        if (check) check.hidden = item.dataset.model !== name;
    });
}

// ===== SESSIONS =====
async function loadSessions() {
    try {
        const res      = await fetch('/api/ai/chat/sessions', {
            headers: { apikey: masterKey },
        });
        const data     = await res.json();
        const sessions = (data.sessions || []).map(s => ({ ...s, pinned: +s.pinned }));
        renderSidebar(sessions);
    } catch (e) {
        console.error('Failed to load sessions', e);
    }
}

function renderSidebar(sessions) {
    chatList.innerHTML = '';

    const groups = groupByDate(sessions);
    const order  = ['Pinned', 'Today', 'Yesterday', 'Last 7 Days', 'Last 30 Days', 'Older'];

    for (const label of order) {
        const items = groups[label] || [];
        if (items.length === 0) continue;

        const header = document.createElement('p');
        header.className = 'px-3 mb-1 mt-3 text-uppercase fw-semibold text-secondary sidebar-section-label';
        header.textContent = label;
        chatList.appendChild(header);

        items.forEach(session => chatList.appendChild(buildSessionItem(session)));
    }
}

function buildSessionItem(session) {
    const item = document.createElement('div');
    item.className = 'chat-list-item d-flex align-items-center' + (session.uuid === currentSessionUuid ? ' active' : '');
    item.dataset.uuid = session.uuid;

    const pinHtml = session.pinned
        ? '<i class="bi bi-pin-fill text-warning me-1" style="font-size:0.7rem;flex-shrink:0;"></i>'
        : '';

    item.innerHTML = `
        <div class="d-flex align-items-center w-100 overflow-hidden">
            ${pinHtml}
            <span class="chat-item-title flex-grow-1">${escHtml(session.title)}</span>
            <div class="chat-item-actions ms-1">
                <button class="btn btn-sm chat-item-menu-btn" type="button" aria-haspopup="true" aria-expanded="false" title="More actions">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <div class="chat-item-menu" role="menu">
                    <button class="chat-item-menu-item pin-btn" type="button" role="menuitem">
                        <i class="bi bi-pin${session.pinned ? '-fill text-warning' : ''} me-2"></i>${session.pinned ? 'Unpin' : 'Pin'}
                    </button>
                    <button class="chat-item-menu-item rename-btn" type="button" role="menuitem">
                        <i class="bi bi-pencil me-2"></i>Rename
                    </button>
                    <div class="chat-item-menu-divider"></div>
                    <button class="chat-item-menu-item delete-btn" type="button" role="menuitem">
                        <i class="bi bi-trash me-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    `;

    item.addEventListener('click', e => {
        if (e.target.closest('.chat-item-actions')) return;
        loadSession(session.uuid);
        // Close history offcanvas on mobile
        const offcanvas = bootstrap?.Offcanvas?.getInstance(document.getElementById('history-offcanvas'));
        offcanvas?.hide();
    });

    const menuBtn = item.querySelector('.chat-item-menu-btn');
    const menu    = item.querySelector('.chat-item-menu');

    menuBtn.addEventListener('click', e => {
        e.stopPropagation();
        const isOpen = menu.classList.contains('show');
        closeAllChatItemMenus();
        if (!isOpen) openChatItemMenu(menuBtn, menu);
    });

    item.querySelector('.pin-btn').addEventListener('click', e => {
        e.stopPropagation();
        closeAllChatItemMenus();
        togglePin(session.uuid, session.pinned);
    });

    item.querySelector('.rename-btn').addEventListener('click', e => {
        e.stopPropagation();
        closeAllChatItemMenus();
        renameSession(session.uuid, session.title);
    });

    item.querySelector('.delete-btn').addEventListener('click', e => {
        e.stopPropagation();
        closeAllChatItemMenus();
        deleteSession(session.uuid);
    });

    return item;
}

function openChatItemMenu(btn, menu) {
    document.body.appendChild(menu);
    menu.classList.add('show');
    btn.setAttribute('aria-expanded', 'true');

    const rect    = btn.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    let top  = rect.bottom + 4;
    let left = rect.right - menuRect.width;
    if (left < 8) left = 8;
    if (top + menuRect.height > window.innerHeight - 8) {
        top = rect.top - menuRect.height - 4;
    }
    menu.style.top  = `${top}px`;
    menu.style.left = `${left}px`;

    menu._ownerBtn = btn;
}

function closeAllChatItemMenus() {
    document.querySelectorAll('.chat-item-menu.show').forEach(menu => {
        menu.classList.remove('show');
        menu.style.top  = '';
        menu.style.left = '';
        if (menu._ownerBtn) {
            menu._ownerBtn.setAttribute('aria-expanded', 'false');
            const actions = menu._ownerBtn.closest('.chat-item-actions');
            if (actions) actions.appendChild(menu);
            menu._ownerBtn = null;
        }
    });
}

document.addEventListener('click', e => {
    if (!e.target.closest('.chat-item-menu') && !e.target.closest('.chat-item-menu-btn')) {
        closeAllChatItemMenus();
    }
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeAllChatItemMenus();
});
window.addEventListener('resize', closeAllChatItemMenus);
window.addEventListener('scroll', closeAllChatItemMenus, true);

function groupByDate(sessions) {
    const now       = new Date();
    const today     = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today); yesterday.setDate(yesterday.getDate() - 1);
    const last7     = new Date(today); last7.setDate(last7.getDate() - 7);
    const last30    = new Date(today); last30.setDate(last30.getDate() - 30);

    const groups = { Pinned: [], Today: [], Yesterday: [], 'Last 7 Days': [], 'Last 30 Days': [], Older: [] };

    sessions.forEach(s => {
        if (s.pinned) { groups.Pinned.push(s); return; }
        const d = new Date(s.updated_at);
        if (d >= today)          groups.Today.push(s);
        else if (d >= yesterday) groups.Yesterday.push(s);
        else if (d >= last7)     groups['Last 7 Days'].push(s);
        else if (d >= last30)    groups['Last 30 Days'].push(s);
        else                     groups.Older.push(s);
    });

    return groups;
}

// ===== LOAD SESSION =====
async function loadSession(uuid) {
    try {
        const res = await fetch(`/api/ai/chat/messages/${uuid}`, {
            headers: { apikey: masterKey },
        });
        if (!res.ok) return;
        const data = await res.json();

        currentSessionUuid = uuid;
        window.history.pushState({}, '', `/admin/ai/chat/${uuid}`);

        if (data.session?.model) {
            const prov = data.session.provider || 'ollama';
            setActiveProviderTab(prov, false);
            setSelectedModel(data.session.model, prov);
        }

        renderMessages(data.messages || []);
        updateSidebarActive(uuid);
    } catch (e) {
        console.error('Failed to load session', e);
    }
}

function renderMessages(messages) {
    welcomeScreen.classList.add('d-none');
    chatThread.innerHTML = '';

    messages.forEach(msg => appendMessage(msg.role, msg.content, false, { model: msg.model, created_at: msg.created_at }, msg.images || [], msg.thinking ?? null, msg.documents || []));

    const last = messages[messages.length - 1];
    if (last && last.role === 'user') {
        appendSessionRetryPrompt(last.content);
    }

    scrollToBottom(true);
}

// ===== SEND MESSAGE =====
async function sendMessage() {
    const message = messageInput.value.trim();
    if ((!message && pendingImages.length === 0 && pendingDocuments.length === 0) || isStreaming) return;

    isStreaming    = true;
    userScrolledUp = false;
    updateSendBtn();

    const imagesToSend    = [...pendingImages];
    const documentsToSend = [...pendingDocuments];
    pendingImages    = [];
    pendingDocuments = [];
    renderPreview();

    lastSentMessage   = message;
    lastSentImages    = [...imagesToSend];
    lastSentDocuments = [...documentsToSend];

    messageInput.value = '';
    autoResizeTextarea();

    welcomeScreen.classList.add('d-none');

    appendMessage('user', message, true, null, imagesToSend.map(img => img.dataUrl), null, documentsToSend);

    const assistantDiv = document.createElement('div');
    assistantDiv.className = 'message message-assistant';
    assistantDiv.innerHTML = '<div class="message-bubble"><span class="typing-indicator"><span></span><span></span><span></span></span></div>';
    chatThread.appendChild(assistantDiv);
    scrollToBottom();

    const bubble = assistantDiv.querySelector('.message-bubble');
    let assistantContent = '';
    let thinkingContent  = '';
    let firstChunk       = true;

    try {
        const response = await fetch('/api/ai/chat/stream', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', apikey: masterKey },
            body: JSON.stringify({
                session_uuid: currentSessionUuid,
                message,
                model:    selectedModel,
                provider: selectedProvider,
                images:   imagesToSend.map(img => img.dataUrl),
                documents: documentsToSend,
            }),
        });

        if (!response.ok || !response.body) {
            showNetworkError(bubble, assistantDiv, 'Request failed.');
            return;
        }

        const reader  = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer    = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const events = buffer.split('\n\n');
            buffer = events.pop();

            for (const block of events) {
                const lines   = block.split('\n');
                let eventType = 'message';
                let eventData = '';

                for (const line of lines) {
                    if (line.startsWith('event: '))     eventType = line.slice(7).trim();
                    else if (line.startsWith('data: ')) eventData = line.slice(6);
                }

                if (!eventData) continue;

                let parsed;
                try { parsed = JSON.parse(eventData); } catch { continue; }

                if (eventType === 'session') {
                    currentSessionUuid = parsed.uuid;
                    window.history.pushState({}, '', `/admin/ai/chat/${parsed.uuid}`);
                    loadSessions();
                } else if (eventType === 'error') {
                    bubble.innerHTML = `<span class="text-danger">${escHtml(parsed.error ?? 'Unknown error')}</span>`;
                } else if (eventType === 'done') {
                    if (thinkingContent) {
                        bubble.innerHTML = renderBubbleHtmlSplit(thinkingContent, assistantContent, false, true);
                    } else {
                        bubble.innerHTML = renderBubbleHtml(assistantContent, false);
                    }
                    addCopyButtons(bubble);
                    applyHighlighting(bubble);
                    assistantDiv.dataset.markdown = thinkingContent ? assistantContent : (parseThinking(assistantContent).response || assistantContent);
                    appendMessageFooter(assistantDiv, { model: parsed.model, created_at: parsed.created_at });
                } else if (parsed.thinking !== undefined) {
                    if (firstChunk) {
                        bubble.innerHTML = '';
                        firstChunk = false;
                    }
                    thinkingContent += parsed.thinking;
                    bubble.innerHTML = renderBubbleHtmlSplit(thinkingContent, assistantContent, true, false);
                    const cursor = document.createElement('span');
                    cursor.className = 'streaming-cursor';
                    bubble.appendChild(cursor);
                    if (!userScrolledUp) scrollToBottom();
                } else if (parsed.content !== undefined) {
                    if (firstChunk) {
                        bubble.innerHTML = '';
                        firstChunk = false;
                    }
                    assistantContent += parsed.content;
                    if (thinkingContent) {
                        bubble.innerHTML = renderBubbleHtmlSplit(thinkingContent, assistantContent, true, true);
                    } else {
                        bubble.innerHTML = renderBubbleHtml(assistantContent, true);
                    }
                    const cursor = document.createElement('span');
                    cursor.className = 'streaming-cursor';
                    bubble.appendChild(cursor);
                    if (!userScrolledUp) scrollToBottom();
                }
            }
        }
    } catch (e) {
        showNetworkError(bubble, assistantDiv, `Error: ${escHtml(e.message)}`);
    } finally {
        isStreaming = false;
        updateSendBtn();
        scrollToBottom();
    }
}

// ===== NEW CHAT =====
function startNewChat() {
    currentSessionUuid = null;
    window.history.pushState({}, '', '/admin/ai/chat');
    welcomeScreen.classList.remove('d-none');
    chatThread.innerHTML = '';
    messageInput.value = '';
    autoResizeTextarea();
    updateSendBtn();
    updateSidebarActive(null);
    messageInput.focus();
}

// ===== SESSION ACTIONS =====
async function togglePin(uuid, currentPinned) {
    await fetch(`/api/ai/chat/session/${uuid}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', apikey: masterKey },
        body: JSON.stringify({ pinned: currentPinned ? 0 : 1 }),
    });
    loadSessions();
}

async function renameSession(uuid, currentTitle) {
    const newTitle = await showRenameModal(currentTitle);
    if (!newTitle || newTitle === currentTitle) return;

    await fetch(`/api/ai/chat/session/${uuid}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', apikey: masterKey },
        body: JSON.stringify({ title: newTitle }),
    });
    loadSessions();
}

async function deleteSession(uuid) {
    const confirmed = await showDeleteModal();
    if (!confirmed) return;

    await fetch(`/api/ai/chat/session/${uuid}`, {
        method: 'DELETE',
        headers: { apikey: masterKey },
    });

    if (currentSessionUuid === uuid) startNewChat();
    loadSessions();
}

// ===== MODALS =====
function showDeleteModal() {
    return new Promise(resolve => {
        const modalEl    = document.getElementById('deleteModal');
        const modal      = bootstrap.Modal.getOrCreateInstance(modalEl);
        const confirmBtn = document.getElementById('deleteConfirmBtn');
        let settled      = false;

        function done(value) {
            if (settled) return;
            settled = true;
            confirmBtn.removeEventListener('click', onConfirm);
            resolve(value);
        }

        function onConfirm() {
            modal.hide();
            done(true);
        }

        confirmBtn.addEventListener('click', onConfirm);
        modalEl.addEventListener('hidden.bs.modal', () => done(false), { once: true });
        modal.show();
    });
}

function showRenameModal(currentTitle) {
    return new Promise(resolve => {
        const modalEl    = document.getElementById('renameModal');
        const modal      = bootstrap.Modal.getOrCreateInstance(modalEl);
        const input      = document.getElementById('renameInput');
        const confirmBtn = document.getElementById('renameConfirmBtn');
        let settled      = false;

        input.value = currentTitle;

        function done(value) {
            if (settled) return;
            settled = true;
            confirmBtn.removeEventListener('click', onConfirm);
            input.removeEventListener('keydown', onKeydown);
            resolve(value);
        }

        function onConfirm() {
            const title = input.value.trim();
            modal.hide();
            done(title || null);
        }

        function onKeydown(e) {
            if (e.key === 'Enter') { e.preventDefault(); onConfirm(); }
        }

        confirmBtn.addEventListener('click', onConfirm);
        input.addEventListener('keydown', onKeydown);
        modalEl.addEventListener('shown.bs.modal', () => input.select(), { once: true });
        modalEl.addEventListener('hidden.bs.modal', () => done(null), { once: true });
        modal.show();
    });
}

// ===== SEARCH =====
function openSearchModal() {
    searchModal.show();
}

async function performSearch(q) {
    try {
        const res  = await fetch('/api/ai/chat/search?q=' + encodeURIComponent(q), {
            headers: { apikey: masterKey },
        });
        const data = await res.json();
        renderSearchResults(data.results || [], q);
    } catch (e) {
        searchResults.innerHTML = '<p class="text-danger text-center small py-4 mb-0">Search failed.</p>';
    }
}

function renderSearchResults(results, q) {
    if (results.length === 0) {
        searchResults.innerHTML = '<p class="text-secondary text-center small py-4 mb-0">No results found.</p>';
        return;
    }

    const titleMatches   = results.filter(r => r.title.toLowerCase().includes(q.toLowerCase()));
    const messageMatches = results.filter(r => r.snippet && !r.title.toLowerCase().includes(q.toLowerCase()));

    const frag = document.createDocumentFragment();

    function addGroup(label, items) {
        if (items.length === 0) return;
        const header = document.createElement('p');
        header.className = 'search-section-label mb-0';
        header.textContent = label;
        frag.appendChild(header);
        items.forEach(r => frag.appendChild(buildSearchResultItem(r, q)));
    }

    if (titleMatches.length > 0 && messageMatches.length > 0) {
        addGroup('Conversations', titleMatches);
        addGroup('Messages', messageMatches);
    } else {
        results.forEach(r => frag.appendChild(buildSearchResultItem(r, q)));
    }

    searchResults.innerHTML = '';
    searchResults.appendChild(frag);
}

function buildSearchResultItem(result, q) {
    const item = document.createElement('div');
    item.className = 'search-result-item';

    const date = new Date(result.updated_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });

    const snippetHtml = result.snippet
        ? `<div class="search-result-snippet">${highlightMatch(result.snippet, q)}</div>`
        : '';

    item.innerHTML = `
        <div class="search-result-title">${highlightMatch(result.title, q)}</div>
        <div class="search-result-meta">${escHtml(date)}</div>
        ${snippetHtml}
    `;

    item.addEventListener('click', () => {
        searchModal.hide();
        loadSession(result.uuid);
    });

    return item;
}

function highlightMatch(text, query) {
    if (!query) return escHtml(text);
    const terms   = [...new Set(query.trim().split(/\s+/).filter(t => t.length >= 2))];
    if (terms.length === 0) return escHtml(text);
    const pattern = terms.map(t => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|');
    const matchRe = new RegExp(`^(${pattern})$`, 'i');
    return text.split(new RegExp(`(${pattern})`, 'gi'))
               .map(p => matchRe.test(p) ? `<mark>${escHtml(p)}</mark>` : escHtml(p))
               .join('');
}

// ===== THINKING =====
function parseThinking(raw) {
    const complete = raw.match(/^<think>([\s\S]*?)<\/think>([\s\S]*)$/);
    if (complete) {
        return { thinking: complete[1].trim(), response: complete[2].trimStart(), incomplete: false };
    }
    if (raw.startsWith('<think>')) {
        return { thinking: raw.slice(7), response: '', incomplete: true };
    }
    return { thinking: null, response: raw, incomplete: false };
}

function renderBubbleHtml(raw, streaming = false) {
    const { thinking, response, incomplete } = parseThinking(raw);
    let html = '';

    if (thinking !== null) {
        const inProgress = incomplete && streaming;
        const label = inProgress ? 'Thinking…' : 'Thinking';
        const cls   = inProgress ? ' thinking-in-progress' : '';
        html += `<details class="thinking-block${cls}"${incomplete ? ' open' : ''}>
            <summary class="thinking-summary">
                <i class="bi bi-lightbulb-fill"></i>
                <span class="thinking-summary-label">${label}</span>
                <i class="bi bi-chevron-down thinking-chevron"></i>
            </summary>
            <div class="thinking-content">${renderMarkdown(thinking)}</div>
        </details>`;
    }

    if (response) html += renderMarkdown(response);
    return html;
}

function renderBubbleHtmlSplit(thinking, content, streaming = false, thinkingComplete = true) {
    let html = '';

    if (thinking) {
        const inProgress = streaming && !thinkingComplete;
        const label = inProgress ? 'Thinking…' : 'Thinking';
        const cls   = inProgress ? ' thinking-in-progress' : '';
        html += `<details class="thinking-block${cls}"${inProgress ? ' open' : ''}>
            <summary class="thinking-summary">
                <i class="bi bi-lightbulb-fill"></i>
                <span class="thinking-summary-label">${label}</span>
                <i class="bi bi-chevron-down thinking-chevron"></i>
            </summary>
            <div class="thinking-content">${renderMarkdown(thinking)}</div>
        </details>`;
    }

    if (content) html += renderMarkdown(content);
    return html;
}

// ===== FILE HANDLING =====
const TEXT_EXTENSIONS = new Set([
    'txt','md','csv','html','htm','js','ts','css','sql','json','xml',
    'yaml','yml','py','php','rb','go','java','sh','log','ini','toml','conf',
]);

function fileIcon(type, name) {
    if (type === 'application/pdf') return 'bi-file-earmark-pdf';
    if (type.startsWith('image/'))  return 'bi-file-earmark-image';
    const ext = name.split('.').pop().toLowerCase();
    if (['js','ts','py','php','rb','go','java','sh'].includes(ext)) return 'bi-file-earmark-code';
    if (['csv','sql'].includes(ext))                                return 'bi-file-earmark-spreadsheet';
    if (['html','htm','xml'].includes(ext))                        return 'bi-file-earmark-code';
    if (['json','yaml','yml','toml','ini','conf'].includes(ext))   return 'bi-file-earmark-code';
    return 'bi-file-earmark-text';
}

function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    e.target.value = '';
    files.forEach(file => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = ev => {
                const dataUrl = ev.target.result;
                pendingImages.push({ dataUrl, base64: dataUrl.split(',')[1], name: file.name });
                renderPreview();
                updateSendBtn();
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            const formData = new FormData();
            formData.append('file', file);
            const chip = addPendingDocChip(file.name, file.type, null);
            fetch('/api/ai/chat/extract-pdf', {
                method: 'POST',
                headers: { apikey: masterKey },
                body: formData,
            })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        chip.remove();
                        alert('PDF error: ' + data.error);
                        return;
                    }
                    chip.remove();
                    pendingDocuments.push({ name: file.name, type: file.type, content: data.text });
                    renderPreview();
                    updateSendBtn();
                })
                .catch(() => { chip.remove(); alert('Failed to extract PDF text.'); });
        } else {
            const ext = file.name.split('.').pop().toLowerCase();
            if (!TEXT_EXTENSIONS.has(ext)) return;
            const reader = new FileReader();
            reader.onload = ev => {
                pendingDocuments.push({ name: file.name, type: file.type || 'text/plain', content: ev.target.result });
                renderPreview();
                updateSendBtn();
            };
            reader.readAsText(file);
        }
    });
}

function addPendingDocChip(name, type, _content) {
    imagePreviewArea.classList.remove('d-none');
    attachBtn.classList.add('has-images');
    const chip = document.createElement('div');
    chip.className = 'doc-preview-item';
    chip.dataset.ready = '0';
    chip.innerHTML = `<i class="bi ${fileIcon(type, name)} me-1"></i><span class="doc-preview-name">${escHtml(name)}</span><span class="doc-preview-spinner spinner-border spinner-border-sm ms-2"></span>`;
    imagePreviewArea.appendChild(chip);
    return chip;
}

function renderPreview() {
    const hasAny = pendingImages.length > 0 || pendingDocuments.length > 0;
    if (!hasAny) {
        imagePreviewArea.classList.add('d-none');
        imagePreviewArea.innerHTML = '';
        attachBtn.classList.remove('has-images');
        return;
    }
    imagePreviewArea.classList.remove('d-none');
    attachBtn.classList.add('has-images');
    imagePreviewArea.innerHTML = '';

    pendingImages.forEach((img, idx) => {
        const item     = document.createElement('div');
        item.className = 'image-preview-item';
        const imgEl    = document.createElement('img');
        imgEl.src      = img.dataUrl;
        imgEl.alt      = img.name;
        const rmBtn    = document.createElement('button');
        rmBtn.className = 'image-preview-remove';
        rmBtn.type      = 'button';
        rmBtn.title     = 'Remove';
        rmBtn.innerHTML = '<i class="bi bi-x"></i>';
        rmBtn.addEventListener('click', () => {
            pendingImages.splice(idx, 1);
            renderPreview();
            updateSendBtn();
        });
        item.appendChild(imgEl);
        item.appendChild(rmBtn);
        imagePreviewArea.appendChild(item);
    });

    pendingDocuments.forEach((doc, idx) => {
        const chip     = document.createElement('div');
        chip.className = 'doc-preview-item';
        chip.dataset.ready = '1';
        chip.innerHTML = `<i class="bi ${fileIcon(doc.type, doc.name)} me-1"></i><span class="doc-preview-name">${escHtml(doc.name)}</span>`;
        const rmBtn    = document.createElement('button');
        rmBtn.className = 'doc-preview-remove';
        rmBtn.type      = 'button';
        rmBtn.title     = 'Remove';
        rmBtn.innerHTML = '<i class="bi bi-x"></i>';
        rmBtn.addEventListener('click', () => {
            pendingDocuments.splice(idx, 1);
            renderPreview();
            updateSendBtn();
        });
        chip.appendChild(rmBtn);
        imagePreviewArea.appendChild(chip);
    });
}

// ===== HELPERS =====
function appendMessage(role, content, animate = true, meta = null, images = [], thinking = null, documents = []) {
    const div = document.createElement('div');
    div.className = `message message-${role}`;

    if (role === 'user') {
        let imagesHtml = '';
        if (images.length > 0) {
            imagesHtml = '<div class="message-images">' +
                images.map(src => `<img class="message-image mb-2" src="${escHtml(src)}" alt="Attached image" loading="lazy">`).join('') +
                '</div>';
        }
        let docsHtml = '';
        if (documents.length > 0) {
            docsHtml = '<div class="message-docs">' +
                documents.map(doc => `<span class="message-doc-chip"><i class="bi ${fileIcon(doc.type || '', doc.name)} me-1"></i>${escHtml(doc.name)}</span>`).join('') +
                '</div>';
        }
        div.innerHTML = `<div class="message-bubble">${imagesHtml}${docsHtml}${content ? escHtml(content) : ''}</div>`;
    } else {
        const bubble = document.createElement('div');
        bubble.className = 'message-bubble';
        if (thinking !== null) {
            bubble.innerHTML = renderBubbleHtmlSplit(thinking, content, false, true);
        } else {
            bubble.innerHTML = renderBubbleHtml(content, false);
        }
        addCopyButtons(bubble);
        applyHighlighting(bubble);
        div.appendChild(bubble);
        div.dataset.markdown = thinking !== null ? content : (parseThinking(content).response || content);
        appendMessageFooter(div, meta);
    }

    chatThread.appendChild(div);
    if (animate && !userScrolledUp) scrollToBottom();
}

function renderMarkdown(text) {
    if (window.marked) {
        return window.marked.parse(text, { breaks: true, gfm: true });
    }
    return escHtml(text).replace(/\n/g, '<br>');
}

function addCopyButtons(container) {
    container.querySelectorAll('pre').forEach(pre => {
        if (pre.querySelector('.copy-code-btn')) return;
        const btn = document.createElement('button');
        btn.className = 'copy-code-btn code-block__copy-btn';
        btn.innerHTML = '<i class="bi bi-clipboard"></i>';
        btn.title = 'Copy';
        btn.addEventListener('click', () => {
            const code = pre.querySelector('code')?.textContent ?? pre.textContent;
            navigator.clipboard.writeText(code).then(() => {
                btn.innerHTML = '<i class="bi bi-check text-light"></i>';
                setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
            });
        });
        pre.appendChild(btn);
    });
}

function appendMessageFooter(messageDiv, meta = null) {
    const footer  = document.createElement('div');
    footer.className = 'message-footer';

    const metaEl = document.createElement('div');
    metaEl.className = 'message-meta';
    if (meta?.model) {
        const s = document.createElement('span');
        s.textContent = meta.model;
        metaEl.appendChild(s);
    }
    if (meta?.created_at) {
        const d = new Date(String(meta.created_at).replace(' ', 'T'));
        const s = document.createElement('span');
        s.textContent = d.toLocaleString('en-GB', {
            day: 'numeric', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
        metaEl.appendChild(s);
    }
    footer.appendChild(metaEl);

    const actions = document.createElement('div');
    actions.className = 'message-actions';
    actions.appendChild(createCopyBtn('bi-clipboard', 'Copy Markdown', () => messageDiv.dataset.markdown ?? ''));
    footer.appendChild(actions);

    messageDiv.appendChild(footer);
}

function createCopyBtn(icon, label, getContent) {
    const btn = document.createElement('button');
    btn.className = 'btn btn-sm btn-primary';
    btn.title = label;
    btn.innerHTML = `<i class="bi ${icon}"></i>`;
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(getContent()).then(() => {
            btn.innerHTML = '<i class="bi bi-check text-dark"></i>';
            setTimeout(() => { btn.innerHTML = `<i class="bi ${icon}></i>`; }, 2000);
        });
    });
    return btn;
}

function applyHighlighting(container) {
    if (!window.hljs) return;
    container.querySelectorAll('pre code').forEach(block => hljs.highlightElement(block));
}

function updateSidebarActive(uuid) {
    document.querySelectorAll('.chat-list-item').forEach(el => {
        el.classList.toggle('active', el.dataset.uuid === uuid);
    });
}

function scrollToBottom(force = false) {
    if (force || !userScrolledUp) {
        window.scrollTo(0, document.body.scrollHeight);
    }
}

function onInputChange() {
    autoResizeTextarea();
    updateSendBtn();
}

function onKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (!sendBtn.disabled) sendMessage();
    }
}

function autoResizeTextarea() {
    messageInput.style.height = 'auto';
    messageInput.style.height = Math.min(messageInput.scrollHeight, 200) + 'px';
}

function updateSendBtn() {
    if (isStreaming) {
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    } else {
        sendBtn.disabled = !messageInput.value.trim() && pendingImages.length === 0 && pendingDocuments.length === 0;
        sendBtn.innerHTML = '<i class="bi bi-arrow-up-short fs-5"></i>';
    }
}

// ===== LIGHTBOX =====
function openLightbox(src) {
    const overlay = document.createElement('div');
    overlay.className = 'lightbox-overlay';
    overlay.innerHTML = `<img class="lightbox-img" src="${escHtml(src)}" alt="Image">`;

    function close() {
        document.removeEventListener('keydown', onKey);
        overlay.remove();
    }

    function onKey(e) {
        if (e.key === 'Escape') close();
    }

    overlay.addEventListener('click', close);
    document.addEventListener('keydown', onKey);
    document.body.appendChild(overlay);
}

document.addEventListener('click', e => {
    const img = e.target.closest('.message-image');
    if (img) openLightbox(img.src);
});

// ===== RETRY =====
function showNetworkError(bubble, assistantDiv, message) {
    bubble.innerHTML = `<div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="text-danger">${message}</span>
        <button class="btn btn-sm btn-outline-secondary retry-btn" type="button">
            <i class="bi bi-arrow-clockwise me-1"></i>Retry
        </button>
    </div>`;
    bubble.querySelector('.retry-btn').addEventListener('click', () => retryMessage(assistantDiv));
}

function retryMessage(assistantDiv) {
    const userDiv = assistantDiv.previousElementSibling;
    if (userDiv?.classList.contains('message-user')) userDiv.remove();
    assistantDiv.remove();
    messageInput.value = lastSentMessage;
    pendingImages    = [...lastSentImages];
    pendingDocuments = [...lastSentDocuments];
    renderPreview();
    autoResizeTextarea();
    updateSendBtn();
    sendMessage();
}

function appendSessionRetryPrompt(userContent) {
    const div = document.createElement('div');
    div.className = 'message message-assistant';
    div.innerHTML = `<div class="message-bubble">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-secondary">The last response didn't complete.</span>
            <button class="btn btn-sm btn-outline-secondary retry-session-btn" type="button">
                <i class="bi bi-arrow-clockwise me-1"></i>Retry
            </button>
        </div>
    </div>`;
    div.querySelector('.retry-session-btn').addEventListener('click', () => {
        div.remove();
        const lastUserDiv = chatThread.lastElementChild;
        if (lastUserDiv?.classList.contains('message-user')) lastUserDiv.remove();
        messageInput.value = userContent;
        autoResizeTextarea();
        updateSendBtn();
        sendMessage();
    });
    chatThread.appendChild(div);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
