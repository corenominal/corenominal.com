<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div id="notes-container" data-master-key="<?= esc(config('ApiKeys')->masterKey) ?>">

    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <h1 class="h4 mb-0">Notes</h1>
        <div class="btn-group" role="group" aria-label="Page actions">
            <a href="<?= site_url('admin/notes/new') ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-circle-fill"></i><span class="d-none d-lg-inline"> New</span>
            </a>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-refresh">
                <i class="bi bi-arrow-clockwise"></i><span class="d-none d-lg-inline"> Refresh</span>
            </button>
        </div>
    </div>

    <div class="mb-3">
        <input type="search" id="notes-search" class="form-control" placeholder="Search notes&hellip;" aria-label="Search notes">
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Note</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody id="notes-tbody">
                <tr><td colspan="2" class="text-center text-muted">Loading&hellip;</td></tr>
            </tbody>
        </table>
    </div>

    <nav id="notes-pagination" aria-label="Notes pagination"></nav>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-delete-note" tabindex="-1" aria-labelledby="modal-delete-note-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-note-label">Delete Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this note? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-delete">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
