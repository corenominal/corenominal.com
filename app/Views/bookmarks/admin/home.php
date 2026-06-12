<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0">Bookmarks Dashboard</h1>
        <a href="<?= site_url('admin/bookmarks/create') ?>" class="btn btn-sm btn-primary flex-shrink-0">
            <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i>Add Bookmark
        </a>
    </div>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['total']) ?></div>
                    <div class="text-secondary small">Total Bookmarks</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['public']) ?></div>
                    <div class="text-secondary small">Public</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['private']) ?></div>
                    <div class="text-secondary small">Private</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <?php
                    $v = $stats['views'];
                    if ($v >= 1_000_000) {
                        $viewsDisplay = round($v / 1_000_000, 1) . 'm';
                    } elseif ($v >= 1_000) {
                        $viewsDisplay = round($v / 1_000, 1) . 'k';
                    } else {
                        $viewsDisplay = (string) $v;
                    }
                    $viewsDisplay = preg_replace('/\.0([km])$/', '$1', $viewsDisplay);
                    ?>
                    <div class="fs-3 fw-bold" title="<?= number_format($stats['views']) ?>"><?= $viewsDisplay ?></div>
                    <div class="text-secondary small">Total Views</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
        <form method="get" action="<?= site_url('admin/bookmarks') ?>" class="d-flex" role="search">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="Search bookmarks&hellip;"
                    value="<?= esc($search) ?>"
                    autocomplete="off"
                    style="width: 220px;"
                >
            </div>
        </form>
        <button type="button" class="btn btn-sm btn-outline-danger" id="btn-delete" disabled>
            <i class="bi bi-trash3-fill me-1" aria-hidden="true"></i>Delete Selected
        </button>
    </div>

    <!-- Bookmarks table -->
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead class="table-secondary">
                <tr>
                    <th style="width: 32px;">
                        <input type="checkbox" id="select-all" class="form-check-input" aria-label="Select all">
                    </th>
                    <th>Title</th>
                    <th class="d-none d-md-table-cell">Tags</th>
                    <th class="d-none d-lg-table-cell">Visibility</th>
                    <th class="d-none d-lg-table-cell">Created</th>
                    <th style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookmarks): ?>
                    <?php foreach ($bookmarks as $bookmark): ?>
                        <tr data-bookmark-id="<?= (int) $bookmark['id'] ?>">
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-check-input row-checkbox"
                                    data-id="<?= (int) $bookmark['id'] ?>"
                                    aria-label="Select <?= esc($bookmark['title']) ?>"
                                >
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (! empty($bookmark['favicon'])): ?>
                                        <img src="<?= esc($bookmark['favicon']) ?>" alt="" width="14" height="14" class="flex-shrink-0" aria-hidden="true">
                                    <?php endif; ?>
                                    <div>
                                        <a href="<?= site_url('admin/bookmarks/' . $bookmark['uuid'] . '/edit') ?>" class="text-body fw-medium text-decoration-none">
                                            <?= esc($bookmark['title']) ?>
                                        </a>
                                        <div class="small text-secondary text-truncate" style="max-width: 260px;">
                                            <a href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-secondary"><?= esc($bookmark['url']) ?></a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php if (! empty($bookmark['tags'])): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach (array_slice(array_filter(array_map('trim', explode(',', $bookmark['tags']))), 0, 4) as $tag): ?>
                                            <span class="badge text-bg-secondary fw-normal"><?= esc($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell">
                                <?php if (! empty($bookmark['private'])): ?>
                                    <span class="badge text-bg-warning">Private</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">Public</span>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell small text-secondary">
                                <?= esc(date('j M Y', strtotime((string) $bookmark['created_at']))) ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= site_url('admin/bookmarks/' . $bookmark['uuid'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger btn-delete-single"
                                        data-id="<?= (int) $bookmark['id'] ?>"
                                        data-title="<?= esc($bookmark['title']) ?>"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-secondary text-center py-4">No bookmarks found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pager): ?>
        <div class="mt-3">
            <?= $pager->links() ?>
        </div>
    <?php endif; ?>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="modal-delete-confirm-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modal-delete-confirm-label">Delete Bookmark</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="delete-modal-count">0</strong> bookmark(s)? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
