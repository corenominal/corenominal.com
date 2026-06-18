<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <h1 class="h3 text-uppercase mb-4">Admin</h1>

    <ul class="list-group mb-4">
        <a href="/admin/startpage" class="list-group-item list-group-item-action">
            <i class="bi bi-slash-square me-2"></i>Startpage
        </a>
    </ul>

    <ul class="list-group mb-4">
        <a href="/admin/blog" class="list-group-item list-group-item-action">
            <i class="bi bi-pencil me-2"></i>Blog
        </a>
        <a href="/admin/status" class="list-group-item list-group-item-action">
            <i class="bi bi-broadcast me-2"></i>Status
        </a>
        <a href="/admin/bookmarks" class="list-group-item list-group-item-action">
            <i class="bi bi-bookmarks me-2"></i>Bookmarks
        </a>
    </ul>

    <ul class="list-group mb-4">
        <a href="/admin/ai/chat" class="list-group-item list-group-item-action">
            <i class="bi bi-chat-dots me-2"></i>AI Chat
        </a>

        <a href="/admin/todo" class="list-group-item list-group-item-action">
            <i class="bi bi-check2-square me-2"></i>Todo
        </a>

        <a href="/admin/notes" class="list-group-item list-group-item-action">
            <i class="bi bi-journal-text me-2"></i>Notes
        </a>
    </ul>

    <ul class="list-group mb-4">
        <a href="/admin/auth" class="list-group-item list-group-item-action">
            <i class="bi bi-shield-lock me-2"></i>Auth
        </a>
        <a href="/admin/social" class="list-group-item list-group-item-action">
            <i class="bi bi-link-45deg me-2"></i>Social
        </a>
        <a href="/admin/metrics" class="list-group-item list-group-item-action">
            <i class="bi bi-graph-up me-2"></i>Metrics
        </a>
    </ul>

<?= $this->endSection() ?>
