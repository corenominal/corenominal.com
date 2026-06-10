<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Auth</li>
        </ol>
    </nav>

    <h1 class="h3 text-uppercase mb-4">Auth</h1>

    <ul class="list-group">
        <a href="/admin/auth/users" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span><i class="bi bi-person me-2"></i>Users</span>
            <span class="badge bg-secondary rounded-pill"><?= $userCount ?></span>
        </a>
        <a href="/admin/auth/groups" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span><i class="bi bi-collection me-2"></i>Groups</span>
            <span class="badge bg-secondary rounded-pill"><?= $groupCount ?></span>
        </a>
        <a href="/admin/auth/apikeys" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span><i class="bi bi-code-slash me-2"></i>API Keys</span>
            <span class="badge bg-secondary rounded-pill"><?= $apikeyCount ?></span>
        </a>
    </ul>

<?= $this->endSection() ?>
