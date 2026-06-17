<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between gap-3 border-bottom mb-4 pb-4">
    <h1 class="h3 mb-0">Search Engines</h1>
    <div class="d-flex gap-2">
        <button id="btn-add" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Add</button>
        <a href="/admin/startpage" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (empty($search_engines)): ?>
    <p class="text-secondary">No search engines yet.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle" id="table-search">
        <thead>
            <tr>
                <th>Phrase</th>
                <th>URL</th>
                <th>Comments</th>
                <th style="width:7rem"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($search_engines as $row): ?>
            <tr>
                <td class="text-nowrap"><?= esc($row['phrase']) ?></td>
                <td class="text-truncate" style="max-width:300px;" title="<?= esc($row['url']) ?>"><?= esc($row['url']) ?></td>
                <td class="text-truncate" style="max-width:200px;"><?= esc($row['comments']) ?></td>
                <td class="text-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-outline-primary btn-edit"
                            data-id="<?= $row['id'] ?>"
                            data-phrase="<?= esc($row['phrase'], 'attr') ?>"
                            data-url="<?= esc($row['url'], 'attr') ?>"
                            data-comments="<?= esc($row['comments'] ?? '', 'attr') ?>"
                            aria-label="Edit"><i class="bi bi-pencil-fill"></i></button>
                        <button class="btn btn-outline-danger btn-delete-row"
                            data-id="<?= $row['id'] ?>"
                            aria-label="Delete"><i class="bi bi-trash-fill"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Form modal (add / edit) -->
<div class="modal fade" id="modal-search-form" tabindex="-1" aria-labelledby="modal-search-form-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-search-form-label">New Search Engine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="search-form-id">
                <div class="mb-3">
                    <label for="search-form-phrase" class="form-label">Phrase</label>
                    <input type="text" class="form-control" id="search-form-phrase" placeholder="e.g. g" required>
                </div>
                <div class="mb-3">
                    <label for="search-form-url" class="form-label">URL</label>
                    <input type="url" class="form-control" id="search-form-url" placeholder="https://google.com/search?q=%s" required>
                    <div class="form-text">Use <code>%s</code> as the search term placeholder.</div>
                </div>
                <div class="mb-3">
                    <label for="search-form-comments" class="form-label">Comments</label>
                    <input type="text" class="form-control" id="search-form-comments">
                </div>
                <div id="search-form-error" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-search-form-save">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-search-delete-confirm" tabindex="-1" aria-labelledby="modal-search-delete-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-search-delete-label">Delete Search Engine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Delete this search engine? This cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btn-search-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
