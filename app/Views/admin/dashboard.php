<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <h1 class="h3 text-uppercase mb-4">Admin</h1>

    <ul class="list-group">
        <a href="/admin/auth" class="list-group-item list-group-item-action">
            <i class="bi bi-shield-lock me-2"></i>Auth
        </a>
        <a href="/admin/social" class="list-group-item list-group-item-action">
            <i class="bi bi-link-45deg me-2"></i>Social
        </a>
        <a href="/admin/status" class="list-group-item list-group-item-action">
            <i class="bi bi-broadcast me-2"></i>Status
        </a>
        <a href="/admin/bookmarks" class="list-group-item list-group-item-action">
            <i class="bi bi-bookmarks me-2"></i>Bookmarks
        </a>
    </ul>

<?= $this->endSection() ?>
