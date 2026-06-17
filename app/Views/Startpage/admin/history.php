<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between gap-3 border-bottom mb-4 pb-4">
    <h1 class="h3 mb-0">History</h1>
    <div class="d-flex gap-2">
        <button id="btn-history-delete" class="btn btn-outline-danger" disabled>
            <i class="bi bi-trash-fill"></i> Delete Selected
        </button>
        <a href="/admin/startpage" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<table id="table-history" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th><input type="checkbox" id="history-select-all" class="form-check-input" aria-label="Select all"></th>
            <th>Date</th>
            <th>Query</th>
            <th class="text-end">Count</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($history as $row): ?>
        <tr data-id="<?= esc($row['id']) ?>">
            <td class="text-center"><input type="checkbox" class="row-select form-check-input" aria-label="Select row"></td>
            <td><?= esc($row['updated_at']) ?></td>
            <td><?= esc($row['q']) ?></td>
            <td class="text-end"><?= esc($row['count']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal fade" id="modal-history-delete-confirm" tabindex="-1" aria-labelledby="modal-history-delete-confirm-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-history-delete-confirm-label">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Delete <strong id="history-delete-modal-count">0</strong> selected record(s)? This cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btn-history-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
