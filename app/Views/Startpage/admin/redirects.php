<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <h1 class="h3 mb-0">Redirects</h1>
    <div class="d-flex gap-2">
        <button id="btn-add" class="btn btn-outline-primary"><i class="bi bi-plus-lg"></i> Add</button>
        <a href="/admin/startpage" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (empty($redirects)): ?>
    <p class="text-secondary">No redirects yet.</p>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle" id="table-redirects">
        <thead>
            <tr>
                <th>Phrase</th>
                <th>URL</th>
                <th>Comments</th>
                <th style="width:7rem"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($redirects as $row): ?>
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
                        <button class="btn btn-outline-primary btn-delete-row"
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
<div class="modal fade" id="modal-redirects-form" tabindex="-1" aria-labelledby="modal-redirects-form-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-redirects-form-label">New Redirect</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="redirects-form-id">
                <div class="mb-3">
                    <label for="redirects-form-phrase" class="form-label">Phrase</label>
                    <input type="text" class="form-control" id="redirects-form-phrase" placeholder="e.g. gh" required>
                </div>
                <div class="mb-3">
                    <label for="redirects-form-url" class="form-label">URL</label>
                    <input type="url" class="form-control" id="redirects-form-url" placeholder="https://example.com" required>
                </div>
                <div class="mb-3">
                    <label for="redirects-form-comments" class="form-label">Comments</label>
                    <input type="text" class="form-control" id="redirects-form-comments">
                </div>
                <div id="redirects-form-error" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-redirects-form-save">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-redirects-delete-confirm" tabindex="-1" aria-labelledby="modal-redirects-delete-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-redirects-delete-label">Delete Redirect</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Delete this redirect? This cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-redirects-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
