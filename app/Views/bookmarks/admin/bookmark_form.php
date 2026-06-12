<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h4 mb-1"><?= esc($title) ?></h1>
        <p class="text-secondary small mb-0">
            <?= $action === 'create' ? 'Fill in the details below to save a new bookmark.' : 'Update the bookmark details below.' ?>
        </p>
    </div>
    <a href="<?= site_url('admin/bookmarks') ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
    </a>
</div>

<!-- Alert area -->
<div id="form-alert" class="alert d-none mb-4" role="alert" aria-live="polite"></div>

<!-- Form + Preview -->
<form
    id="bookmark-form"
    novalidate
    data-action="<?= esc($action) ?>"
    data-uuid="<?= esc($bookmark['uuid'] ?? '') ?>"
    data-image="<?= esc($bookmark['image'] ?? '') ?>"
    data-api-key="<?= esc(config('ApiKeys')->masterKey) ?>"
>
    <div class="row g-4 align-items-start">

        <!-- Left column: form fields -->
        <div class="col-12 col-xl-6">

            <div class="card mb-4">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-bookmark-fill me-2 text-secondary" aria-hidden="true"></i>Bookmark Details</h2>
                </div>
                <div class="card-body">

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="field-title" class="form-label fw-medium">
                            Title <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="field-title"
                            name="title"
                            class="form-control"
                            value="<?= esc($bookmark['title'] ?? '') ?>"
                            maxlength="255"
                            autocomplete="off"
                            required
                        >
                        <div class="invalid-feedback" id="error-title"></div>
                    </div>

                    <!-- URL -->
                    <div class="mb-3">
                        <label for="field-url" class="form-label fw-medium">
                            URL <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="url"
                            id="field-url"
                            name="url"
                            class="form-control"
                            value="<?= esc($bookmark['url'] ?? '') ?>"
                            autocomplete="off"
                            required
                        >
                        <div class="invalid-feedback" id="error-url"></div>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <button type="button" id="btn-screenshot" class="btn btn-sm btn-outline-primary" disabled>
                                <span id="btn-screenshot-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                                <i id="btn-screenshot-icon" class="bi bi-camera me-1" aria-hidden="true"></i>Take Screenshot
                            </button>
                            <span id="screenshot-status" class="small d-none"></span>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="mb-3">
                        <label for="field-tag-input" class="form-label fw-medium">
                            Tags <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <input type="hidden" id="field-tags" name="tags" value="<?= esc($bookmark['tags'] ?? '') ?>">
                        <datalist id="tags-datalist"></datalist>
                        <input
                            type="text"
                            id="field-tag-input"
                            class="form-control"
                            list="tags-datalist"
                            placeholder="Type a tag and press Enter or comma"
                            autocomplete="off"
                        >
                        <div class="invalid-feedback" id="error-tags"></div>
                        <div id="tags-badge-list" class="mt-2 d-flex flex-wrap gap-1" role="list" aria-label="Selected tags"></div>
                        <div class="form-text">Press Enter or comma to add a tag. Click a badge to remove it.</div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-0">
                        <label for="field-notes" class="form-label fw-medium">Notes</label>
                        <textarea
                            id="field-notes"
                            name="notes"
                            class="form-control"
                            rows="8"
                            placeholder="Optional notes in Markdown format"
                        ><?= esc($bookmark['notes'] ?? '') ?></textarea>
                        <div class="form-text">Supports Markdown. Converted to HTML on save.</div>
                    </div>

                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-toggles me-2 text-secondary" aria-hidden="true"></i>Options</h2>
                </div>
                <div class="card-body">

                    <!-- Visibility -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="field-private"
                                name="private"
                                value="1"
                                <?= ! empty($bookmark['private']) ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="field-private">
                                Private <span class="text-secondary small">(hidden from the public listing)</span>
                            </label>
                        </div>
                    </div>

                    <!-- Dashboard -->
                    <div class="mb-0">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="field-dashboard"
                                name="dashboard"
                                value="1"
                                <?= ! empty($bookmark['dashboard']) ? 'checked' : '' ?>
                            >
                            <label class="form-check-label" for="field-dashboard">
                                Show on Dashboard <span class="text-secondary small">(pin to startpage)</span>
                            </label>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= site_url('admin/bookmarks') ?>" class="btn btn-outline-primary">Cancel</a>
                <button type="submit" id="btn-submit" class="btn btn-primary">
                    <span id="btn-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <?php if ($action === 'create'): ?>
                        <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i>Save Bookmark
                    <?php else: ?>
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Update Bookmark
                    <?php endif; ?>
                </button>
            </div>

        </div>
        <!-- /Left column -->

        <!-- Right column: live preview -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-eye me-2 text-secondary" aria-hidden="true"></i>Live Preview</h2>
                </div>
                <div class="card-body">

                    <!-- Placeholder shown when no title/URL entered yet -->
                    <div id="preview-placeholder" class="text-center text-secondary py-4">
                        <i class="bi bi-bookmark fs-2" aria-hidden="true"></i>
                        <p class="mt-2 mb-0 small">Enter a title and URL to see a preview.</p>
                    </div>

                    <!-- Preview card mirrors bookmark_items.php -->
                    <article id="bookmark-preview" class="d-none" aria-label="Bookmark preview">

                        <div id="preview-image-wrap" class="mb-3 rounded overflow-hidden d-none">
                            <img
                                id="preview-image"
                                src=""
                                alt=""
                                class="img-fluid w-100"
                                style="object-fit: cover; max-height: 220px;"
                                loading="lazy"
                                decoding="async"
                            >
                        </div>

                        <div class="d-flex align-items-start gap-2 mb-1">
                            <img id="preview-favicon" src="" alt="" width="16" height="16" class="flex-shrink-0 mt-1 d-none" aria-hidden="true">
                            <h3 class="h6 mb-0 fw-semibold">
                                <a id="preview-title" href="#" target="_blank" rel="noopener noreferrer" class="text-body text-decoration-none">Untitled</a>
                            </h3>
                        </div>

                        <div id="preview-notes" class="mt-2 small text-body-secondary d-none"></div>
                        <div id="preview-tags" class="mt-2 d-flex flex-wrap gap-1 d-none" aria-label="Tags"></div>

                        <div class="mt-2 d-flex align-items-center gap-2">
                            <time id="preview-date" class="small text-secondary"></time>
                            <span id="preview-private" class="badge text-bg-secondary d-none">Private</span>
                        </div>

                    </article>

                </div>
            </div>
        </div>
        <!-- /Right column -->

    </div>
</form>

<?= $this->endSection() ?>
