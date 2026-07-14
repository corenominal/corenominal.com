<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h4 mb-1"><?= esc((string) $title) ?></h1>
        <p class="text-secondary small mb-0">
            <?= $action === 'create' ? 'Write a note for this bike using Markdown.' : 'Update this note below.' ?>
        </p>
    </div>
    <a href="<?= site_url('admin/bikes/' . $bike['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to <?= esc($bike['name'] !== null && $bike['name'] !== '' ? $bike['name'] : ($bike['brand'] . ' ' . $bike['model'])) ?>
    </a>
</div>

<!-- Alert area -->
<div id="form-alert" class="alert d-none mb-4" role="alert" aria-live="polite"></div>

<form
    id="note-form"
    novalidate
    data-action="<?= esc((string) $action) ?>"
    data-bike-id="<?= esc((string) $bike['id']) ?>"
    data-note-id="<?= esc((string) ($note['id'] ?? '')) ?>"
    data-api-key="<?= esc(config('ApiKeys')->masterKey) ?>"
>
    <div class="row g-4 align-items-start mb-4">

        <!-- Left column: editor -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-journal-text me-2 text-secondary" aria-hidden="true"></i>Note</h2>
                </div>
                <div class="card-body">

                    <!-- Title -->
                    <div class="mb-3">
                        <label for="field-title" class="form-label fw-medium">Title</label>
                        <input
                            type="text"
                            id="field-title"
                            name="title"
                            class="form-control"
                            value="<?= esc($note['title'] ?? '') ?>"
                            maxlength="255"
                            autocomplete="off"
                            placeholder="Optional"
                        >
                        <div class="invalid-feedback" id="error-title"></div>
                    </div>

                    <!-- Body -->
                    <div class="mb-0">
                        <label for="field-body" class="form-label fw-medium">
                            Body <span class="text-danger" aria-hidden="true">*</span>
                        </label>
                        <textarea
                            id="field-body"
                            name="body"
                            class="form-control"
                            rows="16"
                            placeholder="Write your note in Markdown&hellip;"
                            required
                        ><?= esc($note['body'] ?? '') ?></textarea>
                        <div class="invalid-feedback" id="error-body"></div>
                        <div class="form-text">Supports Markdown. Converted to HTML on save.</div>
                    </div>

                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-2 justify-content-end mt-4">
                <a href="<?= site_url('admin/bikes/' . $bike['id'] . '/edit') ?>" class="btn btn-outline-primary">Cancel</a>
                <button type="submit" id="btn-submit" class="btn btn-primary">
                    <span id="btn-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <?php if ($action === 'create'): ?>
                        <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i>Save Note
                    <?php else: ?>
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Update Note
                    <?php endif; ?>
                </button>
            </div>
        </div>
        <!-- /Left column -->

        <!-- Right column: live preview -->
        <div class="col-12 col-xl-6">
            <div class="card">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-eye me-2 text-secondary" aria-hidden="true"></i>Preview</h2>
                </div>
                <div class="card-body">
                    <div id="preview-placeholder" class="text-center text-secondary py-4">
                        <i class="bi bi-journal-text fs-2" aria-hidden="true"></i>
                        <p class="mt-2 mb-0 small">Start typing to see a preview.</p>
                    </div>
                    <div id="note-preview" class="d-none"></div>
                </div>
            </div>
        </div>
        <!-- /Right column -->

    </div>
</form>

<!-- Media -->
<?php if ($action === 'edit'): ?>
    <div class="card mb-4" data-note-id="<?= (int) $note['id'] ?>">
        <div class="card-header py-3">
            <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-paperclip me-2 text-secondary" aria-hidden="true"></i>Media</h2>
        </div>
        <div class="card-body">

            <div id="media-alert" class="alert d-none mb-3" role="alert" aria-live="polite"></div>

            <div id="media-gallery" class="row g-2 mb-3">
                <?php foreach ($media as $item): ?>
                    <div class="col-4 col-md-3 media-item" data-media-id="<?= (int) $item['id'] ?>">
                        <div class="position-relative">
                            <?php if (str_starts_with((string) $item['mime_type'], 'image/')): ?>
                                <img
                                    src="<?= site_url('uploads/bikes/notes/media/' . $item['file_name']) ?>"
                                    alt=""
                                    class="img-fluid rounded"
                                    style="aspect-ratio: 1 / 1; object-fit: cover; width: 100%;"
                                >
                            <?php elseif ($item['mime_type'] === 'video/mp4'): ?>
                                <video
                                    src="<?= site_url('uploads/bikes/notes/media/' . $item['file_name']) ?>"
                                    controls
                                    class="rounded"
                                    style="width: 100%; aspect-ratio: 1 / 1; object-fit: cover;"
                                ></video>
                            <?php else: ?>
                                <a
                                    href="<?= site_url('uploads/bikes/notes/media/' . $item['file_name']) ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="d-flex flex-column align-items-center justify-content-center bg-body-secondary rounded text-secondary text-decoration-none p-2"
                                    style="aspect-ratio: 1 / 1; width: 100%;"
                                    title="Open PDF"
                                >
                                    <i class="bi bi-file-earmark-pdf fs-2" aria-hidden="true"></i>
                                    <span class="small text-truncate w-100 text-center mt-1">PDF</span>
                                </a>
                            <?php endif; ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-1 btn-media-delete"
                                data-media-id="<?= (int) $item['id'] ?>"
                                title="Delete"
                            >
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                            <div class="d-flex justify-content-between mt-1">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-media-move" data-direction="up" data-media-id="<?= (int) $item['id'] ?>" title="Move earlier">
                                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-media-move" data-direction="down" data-media-id="<?= (int) $item['id'] ?>" title="Move later">
                                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <label for="field-media-upload" class="form-label fw-medium small">Add media</label>
            <input type="file" id="field-media-upload" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp,image/gif,video/mp4,application/pdf">
            <div class="form-text">Photos, MP4 video, or PDF files.</div>

        </div>
    </div>
<?php else: ?>
    <div class="card mb-4">
        <div class="card-header py-3">
            <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-paperclip me-2 text-secondary" aria-hidden="true"></i>Media</h2>
        </div>
        <div class="card-body">
            <p class="text-secondary small mb-0">Save the note first, then you'll be able to attach photos, video, or PDFs here.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Delete media confirmation modal -->
<div class="modal fade" id="modal-delete-media" tabindex="-1" aria-labelledby="modal-delete-media-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modal-delete-media-label">Delete Media</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this file? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-delete-media">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
