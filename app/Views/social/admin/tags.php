<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
        <li class="breadcrumb-item"><a href="/admin/social">Social</a></li>
        <li class="breadcrumb-item active" aria-current="page">Verification Tags</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 text-uppercase mb-0">Verification Tags</h1>
    <div class="d-flex gap-2">
        <button id="btnBulkDelete" class="btn btn-sm btn-outline-primary d-none">
            <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete Selected (<span id="selectedCount">0</span>)
        </button>
        <button id="btnCreate" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New Tag
        </button>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="Search name, URL…" style="max-width:280px" autocomplete="off">
    <select id="perPageSelect" class="form-select form-select-sm" style="width:auto">
        <option value="10">10 / page</option>
        <option value="20" selected>20 / page</option>
        <option value="50">50 / page</option>
        <option value="100">100 / page</option>
    </select>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0" id="tagsTable">
        <thead>
            <tr>
                <th style="width:2rem"><input type="checkbox" id="selectAll" class="form-check-input" title="Select all"></th>
                <th class="sortable" data-sort="id">ID</th>
                <th class="sortable" data-sort="name">Name</th>
                <th class="sortable" data-sort="url">URL</th>
                <th class="sortable" data-sort="created_at">Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="tagsTableBody">
            <tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>
        </tbody>
    </table>
</div>

<div class="d-flex align-items-center justify-content-between mt-3" id="paginationWrapper">
    <small class="text-muted" id="paginationInfo"></small>
    <nav aria-label="Verification Tags pagination">
        <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
    </nav>
</div>

<!-- Create / Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">New Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="editTagId" value="">
                    <div class="mb-3">
                        <label class="form-label" for="editName">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editName" name="name" autocomplete="off" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editUrl">URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="editUrl" name="url" autocomplete="off" required>
                    </div>
                    <div id="editError" class="alert alert-danger mt-3 d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="editSaveBtn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteModalMsg" class="mb-0"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
