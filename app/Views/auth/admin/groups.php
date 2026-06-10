<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h3 text-uppercase mb-0">Groups</h1>
    <div class="d-flex gap-2">
        <button id="btnBulkDelete" class="btn btn-sm btn-outline-primary d-none">
            <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete Selected (<span id="selectedCount">0</span>)
        </button>
        <button id="btnCreate" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add Membership
        </button>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="Search group, UUID…" style="max-width:280px" autocomplete="off">
    <select id="perPageSelect" class="form-select form-select-sm" style="width:auto">
        <option value="10">10 / page</option>
        <option value="20" selected>20 / page</option>
        <option value="50">50 / page</option>
        <option value="100">100 / page</option>
    </select>
</div>

<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0" id="groupsTable">
        <thead>
            <tr>
                <th style="width:2rem"><input type="checkbox" id="selectAll" class="form-check-input" title="Select all"></th>
                <th class="sortable" data-sort="id">ID</th>
                <th class="sortable" data-sort="group">Group</th>
                <th>User</th>
                <th class="sortable" data-sort="created_at">Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="groupsTableBody">
            <tr><td colspan="6" class="text-center text-muted py-4">Loading…</td></tr>
        </tbody>
    </table>
</div>

<div class="d-flex align-items-center justify-content-between mt-3" id="paginationWrapper">
    <small class="text-muted" id="paginationInfo"></small>
    <nav aria-label="Groups pagination">
        <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
    </nav>
</div>

<!-- Create / Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="editGroupId" value="">
                    <div class="mb-3">
                        <label class="form-label" for="editGroupSelect">Group <span class="text-danger">*</span></label>
                        <select class="form-select" id="editGroupSelect">
                            <option value="">— Select a group —</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="newGroupNameWrap">
                        <label class="form-label" for="editGroupName">New Group Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editGroupName" name="group" autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editUserUuid">User</label>
                        <select class="form-select" id="editUserUuid" name="user_uuid">
                            <option value="">— Select a user —</option>
                        </select>
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
