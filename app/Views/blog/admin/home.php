<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<?php
function blogSortUrl(string $col, ?string $currentSort, string $currentDir, string $search, string $statusFilter): string
{
    $nextDir = ($currentSort === $col && $currentDir === 'ASC') ? 'desc' : 'asc';
    $params  = array_filter([
        'sort'   => $col,
        'dir'    => $nextDir,
        'q'      => $search,
        'status' => $statusFilter,
    ]);

    return site_url('admin/blog') . ($params ? '?' . http_build_query($params) : '');
}

function blogSortIcon(?string $currentSort, string $col, string $currentDir): string
{
    if ($currentSort !== $col) {
        return '<i class="bi bi-chevron-expand" aria-hidden="true"></i>';
    }

    return $currentDir === 'ASC'
        ? '<i class="bi bi-chevron-up" aria-hidden="true"></i>'
        : '<i class="bi bi-chevron-down" aria-hidden="true"></i>';
}
?>

<div class="mb-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0">Blog Admin</h1>
        <a href="<?= site_url('admin/blog/posts/create') ?>" class="btn btn-sm btn-primary flex-shrink-0">
            <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>New Post
        </a>
    </div>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['total_posts']) ?></div>
                    <div class="text-secondary small">Total Posts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['published_posts']) ?></div>
                    <div class="text-secondary small">Published</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['draft_posts']) ?></div>
                    <div class="text-secondary small">Drafts</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['trashed_posts']) ?></div>
                    <div class="text-secondary small">Trashed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['total_tags']) ?></div>
                    <div class="text-secondary small">Tag Entries</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <?php
                    $v = $stats['total_views'];
                    if ($v >= 1_000_000) {
                        $viewsDisplay = round($v / 1_000_000, 1) . 'm';
                    } elseif ($v >= 1_000) {
                        $viewsDisplay = round($v / 1_000, 1) . 'k';
                    } else {
                        $viewsDisplay = (string) $v;
                    }
                    $viewsDisplay = preg_replace('/\.0([km])$/', '$1', $viewsDisplay);
                    ?>
                    <div class="fs-3 fw-bold" title="<?= number_format($stats['total_views']) ?>"><?= $viewsDisplay ?></div>
                    <div class="text-secondary small">Total Views</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
        <form method="get" action="<?= site_url('admin/blog') ?>" class="d-flex gap-2" role="search">
            <?php if ($sort): ?>
                <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                <input type="hidden" name="dir"  value="<?= esc(strtolower($dir)) ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="Search posts&hellip;"
                    value="<?= esc((string) $search) ?>"
                    autocomplete="off"
                    style="width: 200px;"
                >
            </div>
            <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                <option value="" <?= $statusFilter === '' ? 'selected' : '' ?>>All statuses</option>
                <option value="published" <?= $statusFilter === 'published' ? 'selected' : '' ?>>Published</option>
                <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="revision" <?= $statusFilter === 'revision' ? 'selected' : '' ?>>Revision</option>
                <option value="trashed" <?= $statusFilter === 'trashed' ? 'selected' : '' ?>>Trashed</option>
            </select>
        </form>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-delete" disabled>
            <i class="bi bi-trash3-fill me-1" aria-hidden="true"></i>Delete Selected
        </button>
    </div>

    <!-- Posts table -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 32px;">
                        <input type="checkbox" id="select-all" class="form-check-input" aria-label="Select all">
                    </th>
                    <th>Title</th>
                    <th class="d-none d-md-table-cell">Tags</th>
                    <th class="d-none d-lg-table-cell text-end" style="white-space:nowrap;">
                        <a href="<?= blogSortUrl('hitcounter', $sort, $dir, $search, $statusFilter) ?>" class="text-body text-decoration-none">
                            Views <?= blogSortIcon($sort, 'hitcounter', $dir) ?>
                        </a>
                    </th>
                    <th class="d-none d-lg-table-cell" style="white-space:nowrap;">
                        <a href="<?= blogSortUrl('published_at', $sort, $dir, $search, $statusFilter) ?>" class="text-body text-decoration-none">
                            Published <?= blogSortIcon($sort, 'published_at', $dir) ?>
                        </a>
                    </th>
                    <th style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($posts): ?>
                    <?php
                    $statusBadge = [
                        'published' => 'success',
                        'draft'     => 'secondary',
                        'revision'  => 'warning',
                        'trashed'   => 'danger',
                    ];
                    ?>
                    <?php foreach ($posts as $post): ?>
                        <tr data-post-id="<?= (int) $post['id'] ?>" data-post-status="<?= esc($post['status']) ?>">
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-check-input row-checkbox"
                                    data-id="<?= (int) $post['id'] ?>"
                                    aria-label="Select <?= esc($post['title']) ?>"
                                >
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-bg-<?= $statusBadge[$post['status']] ?? 'secondary' ?> flex-shrink-0"><?= esc($post['status']) ?></span>
                                    <div>
                                        <a href="<?= site_url('admin/blog/posts/' . $post['id'] . '/edit') ?>" class="text-body fw-medium text-decoration-none">
                                            <?= esc($post['title']) ?>
                                        </a>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <div class="small text-secondary">
                                                <a href="<?= site_url('blog/posts/' . esc($post['slug'])) ?>" target="_blank" rel="noopener noreferrer" class="text-secondary"><?= esc($post['slug']) ?></a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <?php if (!empty($post['tags'])): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach (array_slice(array_filter(array_map('trim', explode(',', $post['tags']))), 0, 4) as $tag): ?>
                                            <span class="badge text-bg-secondary fw-normal"><?= esc($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell small text-secondary text-end">
                                <?= number_format((int) $post['hitcounter']) ?>
                            </td>
                            <td class="d-none d-lg-table-cell small text-secondary">
                                <?= $post['published_at'] ? esc(date('j M Y', strtotime((string) $post['published_at']))) : '—' ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= site_url('admin/blog/posts/' . $post['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-delete-single"
                                        data-id="<?= (int) $post['id'] ?>"
                                        data-title="<?= esc($post['title']) ?>"
                                        data-status="<?= esc($post['status']) ?>"
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
                        <td colspan="6" class="text-secondary text-center py-4">No posts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pager): ?>
        <div class="mt-3">
            <?= $pager->links('default', 'bootstrap_full') ?>
        </div>
    <?php endif; ?>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="modal-delete-confirm-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modal-delete-confirm-label">Delete Post</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="delete-modal-body">
                Are you sure? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
