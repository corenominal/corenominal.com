<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

<div id="chat-container" data-master-key="<?= esc(config('ApiKeys')->masterKey) ?>" data-session-uuid="<?= esc($uuid ?? '') ?>">

    <!-- Toolbar -->
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
        <button id="new-chat-btn" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">New Chat</span>
        </button>
        <button id="model-btn" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modelModal">
            <i class="bi bi-cpu me-1"></i><span id="model-btn-label">Loading…</span>
        </button>
        <button id="search-btn" class="btn btn-outline-secondary btn-sm" title="Search conversations (⌘K)">
            <i class="bi bi-search"></i> <span class="d-none d-md-inline">Search</span>
        </button>
        <button class="btn btn-outline-secondary btn-sm" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#history-offcanvas" aria-controls="history-offcanvas">
            <i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">History</span>
        </button>
    </div>

    <!-- File input (hidden) -->
    <input type="file" id="image-input" accept="image/*,.pdf,.txt,.md,.csv,.html,.js,.css,.sql,.json,.xml,.yaml,.yml,.ts,.py,.php,.rb,.go,.java,.sh,.log" multiple style="display:none">

    <!-- Chat thread -->
    <div id="chat-thread"></div>

    <!-- Welcome screen (shown when no session loaded) -->
    <div id="welcome-screen" class="text-center py-5 text-secondary">
        <i class="bi bi-chat-dots" style="font-size: 3rem;"></i>
        <h4 class="mt-3 fw-normal">How can I help you today?</h4>
        <p class="small">Select a model and start typing below.</p>
    </div>

    <!-- Input area (sticky bottom) -->
    <div id="input-area">
        <div id="image-preview-area" class="d-none mb-2"></div>
        <div class="position-relative">
            <button id="attach-btn" class="btn chat-attach-btn" type="button" title="Attach file" aria-label="Attach file">
                <i class="bi bi-paperclip"></i>
            </button>
            <textarea
                id="message-input"
                class="form-control chat-textarea"
                placeholder="Message…"
                rows="1"
                aria-label="Chat message"
            ></textarea>
            <button id="send-btn" class="btn btn-primary chat-send-btn" disabled aria-label="Send message">
                <i class="bi bi-arrow-up-short fs-5"></i>
            </button>
        </div>
        <p class="text-center text-secondary small mt-2 mb-0">
            <small>Press <kbd>Enter</kbd> to send &middot; <kbd>Shift+Enter</kbd> for new line</small>
        </p>
    </div>

</div>

<!-- History offcanvas (right side) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="history-offcanvas" aria-labelledby="historyOffcanvasLabel" style="width:300px">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="historyOffcanvasLabel"><i class="bi bi-clock-history me-2"></i>History</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <nav id="chat-list" class="flex-grow-1 overflow-y-auto py-2" aria-label="Conversation history"></nav>
    </div>
</div>

<!-- Search modal -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-label="Search conversations" aria-hidden="true">
    <div class="modal-dialog" style="max-width:620px;margin-top:10vh">
        <div class="modal-content shadow-lg">
            <div class="p-3 border-bottom">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0 ps-1 pe-2 text-secondary">
                        <i class="bi bi-search fs-5"></i>
                    </span>
                    <input type="search" id="search-input"
                           class="form-control form-control-lg border-0 shadow-none bg-transparent ps-0"
                           placeholder="Search conversations and messages…"
                           autocomplete="off" spellcheck="false">
                    <button type="button" class="btn-close my-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div id="search-results" class="search-results-body">
                <p class="text-secondary text-center small py-4 mb-0">Type to search…</p>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-trash me-2"></i>Delete Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-secondary">
                Are you sure you want to delete this conversation? This cannot be undone.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="deleteConfirmBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Rename modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" id="renameModalLabel"><i class="bi bi-pencil me-2"></i>Rename Conversation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="renameInput" placeholder="Conversation name" maxlength="255">
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="renameConfirmBtn">Rename</button>
            </div>
        </div>
    </div>
</div>

<!-- Model selection modal -->
<div class="modal fade" id="modelModal" tabindex="-1" aria-labelledby="modelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modelModalLabel"><i class="bi bi-gear-fill me-2"></i>AI Model</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" id="model-list">
                <p class="text-secondary text-center small py-4 mb-0">Loading models…</p>
            </div>
            <div class="modal-footer border-0 d-block pt-0">
                <p class="text-secondary small mb-0">The selected model will be used for all AI actions.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
