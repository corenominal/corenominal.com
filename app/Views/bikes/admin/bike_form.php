<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h4 mb-1"><?= esc((string) $title) ?></h1>
        <p class="text-secondary small mb-0">
            <?= $action === 'create' ? 'Fill in the details below to add a new bike.' : 'Update the bike details below.' ?>
        </p>
    </div>
    <a href="<?= site_url('admin/bikes') ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
    </a>
</div>

<!-- Alert area -->
<div id="form-alert" class="alert d-none mb-4" role="alert" aria-live="polite"></div>

<form
    id="bike-form"
    novalidate
    data-action="<?= esc((string) $action) ?>"
    data-id="<?= esc((string) ($bike['id'] ?? '')) ?>"
    data-api-key="<?= esc(config('ApiKeys')->masterKey) ?>"
>
    <div class="row g-4 align-items-start">

        <div class="col-12 col-xl-7">

            <div class="card mb-4">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-bicycle me-2 text-secondary" aria-hidden="true"></i>Bike Details</h2>
                </div>
                <div class="card-body">

                    <!-- Name -->
                    <div class="mb-3">
                        <label for="field-name" class="form-label fw-medium">Nickname</label>
                        <input
                            type="text"
                            id="field-name"
                            name="name"
                            class="form-control"
                            value="<?= esc($bike['name'] ?? '') ?>"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="e.g. Winter bike"
                        >
                        <div class="invalid-feedback" id="error-name"></div>
                    </div>

                    <div class="row g-3">
                        <!-- Brand -->
                        <div class="col-6 mb-3">
                            <label for="field-brand" class="form-label fw-medium">
                                Brand <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                id="field-brand"
                                name="brand"
                                class="form-control"
                                value="<?= esc($bike['brand'] ?? '') ?>"
                                maxlength="150"
                                autocomplete="off"
                                required
                            >
                            <div class="invalid-feedback" id="error-brand"></div>
                        </div>

                        <!-- Model -->
                        <div class="col-6 mb-3">
                            <label for="field-model" class="form-label fw-medium">
                                Model <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <input
                                type="text"
                                id="field-model"
                                name="model"
                                class="form-control"
                                value="<?= esc($bike['model'] ?? '') ?>"
                                maxlength="150"
                                autocomplete="off"
                                required
                            >
                            <div class="invalid-feedback" id="error-model"></div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Year -->
                        <div class="col-6 mb-3">
                            <label for="field-year" class="form-label fw-medium">Year</label>
                            <input
                                type="number"
                                id="field-year"
                                name="year"
                                class="form-control"
                                value="<?= esc((string) ($bike['year'] ?? '')) ?>"
                                min="1900"
                                max="2100"
                                autocomplete="off"
                            >
                            <div class="invalid-feedback" id="error-year"></div>
                        </div>

                        <!-- Status -->
                        <div class="col-6 mb-3">
                            <label for="field-status" class="form-label fw-medium">
                                Status <span class="text-danger" aria-hidden="true">*</span>
                            </label>
                            <?php $status = $bike['status'] ?? 'active'; ?>
                            <select id="field-status" name="status" class="form-select" required>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="retired" <?= $status === 'retired' ? 'selected' : '' ?>>Retired</option>
                                <option value="sold" <?= $status === 'sold' ? 'selected' : '' ?>>Sold</option>
                            </select>
                            <div class="invalid-feedback" id="error-status"></div>
                        </div>
                    </div>

                    <!-- Total km -->
                    <div class="mb-3">
                        <label for="field-total-km" class="form-label fw-medium">Total Distance Ridden (km)</label>
                        <input
                            type="number"
                            id="field-total-km"
                            name="total_km"
                            class="form-control"
                            value="<?= esc((string) ($bike['total_km'] ?? '0')) ?>"
                            min="0"
                            step="0.1"
                            autocomplete="off"
                        >
                        <div class="form-text">A manually-updated running total. Update this whenever you like.</div>
                        <div class="invalid-feedback" id="error-total_km"></div>
                    </div>

                    <!-- Components -->
                    <div class="mb-3">
                        <label for="field-components" class="form-label fw-medium">Components</label>
                        <textarea
                            id="field-components"
                            name="components"
                            class="form-control"
                            rows="6"
                            placeholder="e.g. Groupset: Shimano 105&#10;Wheels: Mavic Aksium&#10;Tyres: Continental GP5000"
                        ><?= esc($bike['components'] ?? '') ?></textarea>
                        <div class="form-text">Free text — list groupset, wheels, tyres, or anything else worth noting.</div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-0">
                        <label for="field-notes" class="form-label fw-medium">Notes</label>
                        <textarea
                            id="field-notes"
                            name="notes"
                            class="form-control"
                            rows="4"
                            placeholder="Optional additional notes"
                        ><?= esc($bike['notes'] ?? '') ?></textarea>
                    </div>

                </div>
            </div>

            <!-- Submit -->
            <div class="d-flex gap-2 justify-content-end">
                <a href="<?= site_url('admin/bikes') ?>" class="btn btn-outline-primary">Cancel</a>
                <button type="submit" id="btn-submit" class="btn btn-primary">
                    <span id="btn-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <?php if ($action === 'create'): ?>
                        <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i>Save Bike
                    <?php else: ?>
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Update Bike
                    <?php endif; ?>
                </button>
            </div>

        </div>
        <!-- /Left column -->

        <!-- Right column: photos -->
        <div class="col-12 col-xl-5">
            <div class="card">
                <div class="card-header py-3">
                    <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-images me-2 text-secondary" aria-hidden="true"></i>Photos</h2>
                </div>
                <div class="card-body">

                    <?php if ($action === 'create'): ?>
                        <p class="text-secondary small mb-0">Save the bike first, then you'll be able to upload photos here.</p>
                    <?php else: ?>

                        <div id="photo-alert" class="alert d-none mb-3" role="alert" aria-live="polite"></div>

                        <div id="photo-gallery" class="row g-2 mb-3">
                            <?php foreach ($photos as $photo): ?>
                                <div class="col-4 photo-item" data-photo-id="<?= (int) $photo['id'] ?>">
                                    <div class="position-relative">
                                        <img
                                            src="<?= site_url('uploads/bikes/media/' . $photo['file_name']) ?>"
                                            alt=""
                                            class="img-fluid rounded"
                                            style="aspect-ratio: 1 / 1; object-fit: cover; width: 100%;"
                                        >
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary position-absolute top-0 end-0 m-1 btn-photo-delete"
                                            data-photo-id="<?= (int) $photo['id'] ?>"
                                            title="Delete photo"
                                        >
                                            <i class="bi bi-trash3" aria-hidden="true"></i>
                                        </button>
                                        <div class="d-flex justify-content-between mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-photo-move" data-direction="up" data-photo-id="<?= (int) $photo['id'] ?>" title="Move earlier">
                                                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-photo-move" data-direction="down" data-photo-id="<?= (int) $photo['id'] ?>" title="Move later">
                                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <label for="field-photo-upload" class="form-label fw-medium small">Add a photo</label>
                        <input type="file" id="field-photo-upload" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp,image/gif">
                        <div class="form-text">PNG, JPEG, WebP or GIF. The first photo is used as the cover image.</div>

                    <?php endif; ?>

                </div>
            </div>
        </div>
        <!-- /Right column -->

    </div>
</form>

<!-- Delete photo confirmation modal -->
<div class="modal fade" id="modal-delete-photo" tabindex="-1" aria-labelledby="modal-delete-photo-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modal-delete-photo-label">Delete Photo</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this photo? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-confirm-delete-photo">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
