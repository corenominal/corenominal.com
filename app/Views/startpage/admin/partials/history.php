<div class="d-flex justify-content-between align-items-center mb-2">
    <button id="btn-history-delete" class="btn btn-outline-primary btn-sm" disabled>
        <i class="bi bi-trash-fill"></i> Delete
    </button>
    <a href="/admin/startpage/history" class="btn btn-outline-primary btn-sm"><i class="bi bi-gear-fill"></i> Manage</a>
</div>

<table style="width:100%" id="table-history" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th><input type="checkbox" id="history-select-all" class="form-check-input" aria-label="Select all rows on this page"></th>
            <th>Date</th>
            <th>Query</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($history as $row): ?>
        <tr data-id="<?= esc($row['id']) ?>">
            <td class="text-center"><input type="checkbox" class="row-select form-check-input" aria-label="Select row"></td>
            <td><?= esc($row['updated_at']) ?></td>
            <td><?= esc($row['q']) ?></td>
            <td><?= esc($row['count']) ?></td>
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
                Are you sure you want to delete <strong id="history-delete-modal-count">0</strong> selected record(s)? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-history-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>
