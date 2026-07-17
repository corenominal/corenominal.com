<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4 pb-3 border-bottom">
    <div>
        <h1 class="h4 mb-1"><?= esc((string) $title) ?></h1>
        <p class="text-secondary small mb-0">
            <?= $action === 'create' ? 'Upload a Garmin .fit file to create a new ride.' : 'Update the ride details below.' ?>
        </p>
    </div>
    <a href="<?= site_url('admin/rides') ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
    </a>
</div>

<!-- Alert area -->
<div id="form-alert" class="alert d-none mb-4" role="alert" aria-live="polite"></div>

<?php if ($action === 'create'): ?>

    <div class="card" id="upload-card" style="max-width: 40rem;" data-api-key="<?= esc(config('ApiKeys')->masterKey) ?>">
        <div class="card-header py-3">
            <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-cloud-arrow-up me-2 text-secondary" aria-hidden="true"></i>Upload Ride</h2>
        </div>
        <div class="card-body">
            <p class="text-secondary small">
                Export the original activity file from Garmin Connect (Activity &rarr; &hellip; &rarr; Export Original)
                and upload it here. A .zip works too &mdash; if Garmin gives you one, it's unzipped automatically.
                Title, notes, bike assignment and photos can be added afterwards.
            </p>

            <label for="field-fit-upload" class="form-label fw-medium">Garmin .fit file</label>
            <input type="file" id="field-fit-upload" class="form-control" accept=".fit,.zip">
            <div class="invalid-feedback" id="error-fit"></div>

            <label for="field-ride-type" class="form-label fw-medium mt-3">Ride Type</label>
            <select id="field-ride-type" class="form-select">
                <option value="leisure" selected>Leisure</option>
                <option value="race">Race</option>
                <option value="commute">Commute</option>
                <option value="training">Training</option>
                <option value="indoor">Indoor / Turbo</option>
                <option value="sportive">Sportive / Charity Ride</option>
            </select>

            <div class="d-flex justify-content-end mt-4">
                <button type="button" id="btn-upload" class="btn btn-primary">
                    <span id="btn-upload-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                    <i class="bi bi-cloud-arrow-up-fill me-1" aria-hidden="true"></i>Upload Ride
                </button>
            </div>
        </div>
    </div>

<?php else: ?>

    <form
        id="ride-form"
        novalidate
        data-action="<?= esc((string) $action) ?>"
        data-id="<?= esc((string) ($ride['id'] ?? '')) ?>"
        data-api-key="<?= esc(config('ApiKeys')->masterKey) ?>"
    >
        <div class="row g-4 align-items-start">

            <div class="col-12 col-xl-7">

                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-map me-2 text-secondary" aria-hidden="true"></i>Ride Details</h2>
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
                                value="<?= esc($ride['title'] ?? '') ?>"
                                maxlength="255"
                                autocomplete="off"
                                placeholder="e.g. Sunday club run"
                            >
                            <div class="invalid-feedback" id="error-title"></div>
                        </div>

                        <div class="row g-3">
                            <!-- Bike -->
                            <div class="col-4 mb-3">
                                <label for="field-bike" class="form-label fw-medium">Bike</label>
                                <select id="field-bike" name="bike_id" class="form-select">
                                    <option value="">&mdash; Unassigned &mdash;</option>
                                    <?php foreach ($bikes as $bike): ?>
                                        <?php $bikeLabel = $bike['name'] !== null && $bike['name'] !== '' ? $bike['name'] : trim($bike['brand'] . ' ' . $bike['model']); ?>
                                        <option value="<?= (int) $bike['id'] ?>" <?= (int) ($ride['bike_id'] ?? 0) === (int) $bike['id'] ? 'selected' : '' ?>>
                                            <?= esc($bikeLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback" id="error-bike_id"></div>
                            </div>

                            <!-- Ride Type -->
                            <div class="col-4 mb-3">
                                <label for="field-ride-type" class="form-label fw-medium">Ride Type</label>
                                <?php $rideType = $ride['ride_type'] ?? 'leisure'; ?>
                                <select id="field-ride-type" name="ride_type" class="form-select">
                                    <option value="leisure" <?= $rideType === 'leisure' ? 'selected' : '' ?>>Leisure</option>
                                    <option value="race" <?= $rideType === 'race' ? 'selected' : '' ?>>Race</option>
                                    <option value="commute" <?= $rideType === 'commute' ? 'selected' : '' ?>>Commute</option>
                                    <option value="training" <?= $rideType === 'training' ? 'selected' : '' ?>>Training</option>
                                    <option value="indoor" <?= $rideType === 'indoor' ? 'selected' : '' ?>>Indoor / Turbo</option>
                                    <option value="sportive" <?= $rideType === 'sportive' ? 'selected' : '' ?>>Sportive / Charity Ride</option>
                                </select>
                                <div class="invalid-feedback" id="error-ride_type"></div>
                            </div>

                            <!-- Distance -->
                            <div class="col-4 mb-3">
                                <label for="field-distance" class="form-label fw-medium">Distance (km)</label>
                                <input
                                    type="number"
                                    id="field-distance"
                                    name="distance_km"
                                    class="form-control"
                                    value="<?= esc((string) ($ride['distance_km'] ?? '0')) ?>"
                                    min="0"
                                    step="0.01"
                                    autocomplete="off"
                                >
                                <div class="form-text">Drives the assigned bike's tracked distance.</div>
                                <div class="invalid-feedback" id="error-distance_km"></div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-0">
                            <label for="field-notes" class="form-label fw-medium">Notes</label>
                            <textarea
                                id="field-notes"
                                name="notes"
                                class="form-control"
                                rows="4"
                                placeholder="Optional notes about this ride"
                            ><?= esc($ride['notes'] ?? '') ?></textarea>
                        </div>

                    </div>
                </div>

                <!-- Stats -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-bar-chart-fill me-2 text-secondary" aria-hidden="true"></i>Stats</h2>
                    </div>
                    <div class="card-body">
                        <?php
                        $formatDuration = static function (?int $seconds): string {
                            if ($seconds === null) {
                                return '—';
                            }
                            $h = intdiv($seconds, 3600);
                            $m = intdiv($seconds % 3600, 60);
                            $s = $seconds % 60;

                            return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
                        };
                        ?>
                        <div class="row g-3 small">
                            <div class="col-6 col-md-4">
                                <div class="text-secondary">Moving Time</div>
                                <div class="fw-medium"><?= esc($formatDuration($ride['moving_time_seconds'] ?? null)) ?></div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-secondary">Elapsed Time</div>
                                <div class="fw-medium"><?= esc($formatDuration($ride['elapsed_time_seconds'] ?? null)) ?></div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-secondary">Elevation Gain</div>
                                <div class="fw-medium"><?= $ride['elevation_gain_m'] !== null ? number_format((float) $ride['elevation_gain_m'], 0) . ' m' : '—' ?></div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-secondary">Avg Speed</div>
                                <div class="fw-medium"><?= $ride['avg_speed_kmh'] !== null ? number_format((float) $ride['avg_speed_kmh'], 1) . ' km/h' : '—' ?></div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-secondary">Max Speed</div>
                                <div class="fw-medium"><?= $ride['max_speed_kmh'] !== null ? number_format((float) $ride['max_speed_kmh'], 1) . ' km/h' : '—' ?></div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="text-secondary">Started</div>
                                <div class="fw-medium"><?= $ride['started_at'] ? esc(date('j M Y, H:i', strtotime($ride['started_at']))) : '—' ?></div>
                            </div>
                            <?php if ($ride['avg_heart_rate'] !== null): ?>
                                <div class="col-6 col-md-4">
                                    <div class="text-secondary">Avg Heart Rate</div>
                                    <div class="fw-medium"><?= (int) $ride['avg_heart_rate'] ?> bpm</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($ride['max_heart_rate'] !== null): ?>
                                <div class="col-6 col-md-4">
                                    <div class="text-secondary">Max Heart Rate</div>
                                    <div class="fw-medium"><?= (int) $ride['max_heart_rate'] ?> bpm</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($ride['avg_cadence'] !== null): ?>
                                <div class="col-6 col-md-4">
                                    <div class="text-secondary">Avg Cadence</div>
                                    <div class="fw-medium"><?= (int) $ride['avg_cadence'] ?> rpm</div>
                                </div>
                            <?php endif; ?>
                            <?php if ($ride['avg_power'] !== null): ?>
                                <div class="col-6 col-md-4">
                                    <div class="text-secondary">Avg Power</div>
                                    <div class="fw-medium"><?= (int) $ride['avg_power'] ?> W</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($ride['fit_file_name']): ?>
                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <div class="text-secondary small mb-0">Source: <code><?= esc($ride['fit_file_name']) ?></code></div>
                                <button type="button" id="btn-reparse" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Re-parse from source
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Route map -->
                <div class="card mb-4">
                    <div class="card-header py-3">
                        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-geo-alt-fill me-2 text-secondary" aria-hidden="true"></i>Route Map</h2>
                    </div>
                    <div class="card-body">
                        <?php if (! empty($ride['trackpoints_json'])): ?>
                            <div id="ride-map" style="height: 400px; border-radius: var(--bs-border-radius);"></div>
                            <div class="mt-3" style="height: 160px;">
                                <canvas id="ride-elevation-chart"></canvas>
                            </div>
                            <div id="ride-heart-rate-chart-container" class="mt-3" style="height: 140px;"></div>
                            <script type="application/json" id="ride-trackpoints"><?= $ride['trackpoints_json'] ?></script>
                        <?php else: ?>
                            <p class="text-secondary small mb-0">No GPS data available for this ride (indoor/trainer ride?).</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2 justify-content-end">
                    <a href="<?= site_url('admin/rides') ?>" class="btn btn-outline-primary">Cancel</a>
                    <button type="submit" id="btn-submit" class="btn btn-primary">
                        <span id="btn-submit-spinner" class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        <i class="bi bi-check-circle-fill me-1" aria-hidden="true"></i>Update Ride
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

                        <div id="photo-alert" class="alert d-none mb-3" role="alert" aria-live="polite"></div>

                        <div id="photo-gallery" class="row g-2 mb-3">
                            <?php foreach ($photos as $photo): ?>
                                <div class="col-4 photo-item" data-photo-id="<?= (int) $photo['id'] ?>">
                                    <div class="position-relative">
                                        <img
                                            src="<?= site_url('uploads/rides/media/' . $photo['file_name']) ?>"
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

<?php endif; ?>

<?= $this->endSection() ?>
