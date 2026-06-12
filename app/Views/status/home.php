<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Status Timeline</h1>
        <form method="get" action="<?= site_url('status') ?>" role="search" aria-label="Search statuses" class="d-flex">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    id="timeline-search"
                    name="q"
                    class="form-control"
                    placeholder="Search&hellip;"
                    value="<?= esc((string)$searchQuery) ?>"
                    autocomplete="off"
                    style="width: 160px;"
                >
            </div>
        </form>
    </div>

    <?php if (user_in_group('administrators')): ?>
    <div class="mb-5" id="timeline-compose">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="h6 mb-0 fw-semibold" id="compose-form-title">New Status</h2>
            <div class="d-flex align-items-center gap-2">
                <?php if ($draftCount > 0): ?>
                <button type="button" class="btn btn-sm btn-outline-primary" id="drafts-btn" data-bs-toggle="modal" data-bs-target="#drafts-modal">
                    <i class="bi bi-journal-text me-1" aria-hidden="true"></i>Drafts <span class="badge text-bg-secondary ms-1" id="drafts-count-badge"><?= (int) $draftCount ?></span>
                </button>
                <?php endif; ?>
                <!-- <div class="btn-group btn-group-sm" role="group" aria-label="AI actions"> -->
                    <button type="button" class="btn btn-sm btn-outline-primary" id="ai-rewrite-btn" disabled>
                        <i class="bi bi-stars me-1" aria-hidden="true"></i>AI
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="ai-settings-btn" title="AI model settings" disabled>
                        <i class="bi bi-gear" aria-hidden="true"></i>
                    </button>
                <!-- </div> -->
                <button type="button" class="btn btn-sm btn-outline-primary d-none" id="compose-cancel-btn">Cancel edit</button>
            </div>
        </div>
        <form id="compose-form" novalidate>
            <input type="hidden" id="compose-status-id" value="0">
            <input type="hidden" id="compose-draft-id" value="0">
            <textarea
                class="form-control mb-1"
                id="compose-content"
                name="content"
                rows="3"
                placeholder="What's happening?"
                maxlength="500"
            ></textarea>
            <div class="d-flex justify-content-end mb-2">
                <span id="compose-char-count" class="small text-secondary">500</span>
            </div>
            <div class="d-none mb-3" id="ai-rewrite-card">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between py-2">
                        <span class="small fw-semibold"><i class="bi bi-stars me-1" aria-hidden="true"></i>AI Suggestions</span>
                        <button type="button" class="btn-close" id="ai-rewrite-dismiss" aria-label="Dismiss suggestions"></button>
                    </div>
                    <div class="card-body p-0" id="ai-rewrite-card-body"></div>
                </div>
            </div>
            <div class="d-none mb-3" id="compose-existing-media">
                <p class="small fw-semibold mb-2">Attached media</p>
                <div id="compose-existing-media-list" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div id="compose-pending-uploads"></div>
            <div class="d-flex gap-2 align-items-center flex-wrap mt-2">
                <button type="submit" class="btn btn-sm btn-primary" id="compose-submit-btn"><i class="bi bi-send me-1" aria-hidden="true"></i>Post</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="compose-add-video-btn"><i class="bi bi-paperclip me-1" aria-hidden="true"></i>Media</button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="compose-save-draft-btn" formnovalidate><i class="bi bi-journal-plus me-1" aria-hidden="true"></i>Draft</button>
                <?php if ($mastodonEnabled): ?>
                <div class="form-check form-switch ms-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="compose-mastodon-switch" checked>
                    <label class="form-check-label text-secondary small" for="compose-mastodon-switch">Mastodon</label>
                </div>
                <?php endif; ?>
                <span class="ms-auto text-end small" id="compose-status-msg" aria-live="polite"></span>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <div
        id="timeline-items"
        data-load-url="<?= site_url('status/timeline/load') ?>"
        data-offset="<?= count($statuses) ?>"
        data-limit="<?= (int) $statusBatchSize ?>"
        data-has-more="<?= $hasMoreStatuses ? '1' : '0' ?>"
        data-search="<?= esc((string) $searchQuery) ?>"
    >
        <?= view('status/partials/timeline_items', [
            'statuses'        => $statuses,
            'mastodonHandle'  => $mastodonHandle,
            'mastodonProfile' => $mastodonProfile,
        ]) ?>
    </div>

    <div id="timeline-loader" class="py-3 text-center text-secondary small" aria-live="polite" style="display: none;">
        Loading&hellip;
    </div>
    <div id="timeline-observer" aria-hidden="true"></div>
</div>

<!-- Image preview modal -->
<div class="modal fade" id="timeline-image-modal" tabindex="-1" aria-labelledby="timeline-image-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-6" id="timeline-image-modal-label">Image Preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div id="timeline-image-modal-img-wrap" class="mx-auto">
                    <img id="timeline-image-modal-img" src="" alt="" class="img-fluid rounded" style="max-height: 80vh;">
                </div>
                <p id="timeline-image-modal-caption" class="text-secondary small mt-2 mb-0"></p>
            </div>
        </div>
    </div>
</div>

<?php if (user_in_group('administrators')): ?>

<!-- Delete confirmation modal -->
<div class="modal fade" id="delete-status-modal" tabindex="-1" aria-labelledby="delete-status-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="delete-status-modal-label">Delete status</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this status? This cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="delete-status-confirm-btn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Drafts modal -->
<div class="modal fade" id="drafts-modal" tabindex="-1" aria-labelledby="drafts-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="drafts-modal-label"><i class="bi bi-journal-text me-2" aria-hidden="true"></i>Drafts</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="drafts-modal-body">
                <p class="text-secondary">Loading drafts&hellip;</p>
            </div>
        </div>
    </div>
</div>

<!-- AI settings modal -->
<div class="modal fade" id="ai-settings-modal" tabindex="-1" aria-labelledby="ai-settings-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="ai-settings-modal-label"><i class="bi bi-gear me-2" aria-hidden="true"></i>AI Model</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="ai-settings-modal-body">
                <p class="text-secondary">Loading models&hellip;</p>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
