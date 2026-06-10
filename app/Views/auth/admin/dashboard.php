<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
            <li class="breadcrumb-item active" aria-current="page">Auth</li>
        </ol>
    </nav>

    <h1 class="h3 text-uppercase mb-4">Auth</h1>

    <div class="row g-3">
        <div class="col-sm-6 col-lg-4">
            <a href="/admin/auth/users" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-person fs-2 text-secondary flex-shrink-0"></i>
                        <div>
                            <div class="fs-4 fw-semibold lh-1"><?= $userCount ?></div>
                            <div class="text-muted small text-uppercase">Users</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="/admin/auth/groups" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-collection fs-2 text-secondary flex-shrink-0"></i>
                        <div>
                            <div class="fs-4 fw-semibold lh-1"><?= $groupCount ?></div>
                            <div class="text-muted small text-uppercase">Groups</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-sm-6 col-lg-4">
            <a href="/admin/auth/apikeys" class="text-decoration-none">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <i class="bi bi-code-slash fs-2 text-secondary flex-shrink-0"></i>
                        <div>
                            <div class="fs-4 fw-semibold lh-1"><?= $apikeyCount ?></div>
                            <div class="text-muted small text-uppercase">API Keys</div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

<?= $this->endSection() ?>
