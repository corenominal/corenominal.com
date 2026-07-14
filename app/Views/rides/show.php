<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= esc($backUrl) ?>" class="text-secondary text-decoration-none small">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i><?= esc($backLabel) ?>
    </a>
</div>

<div class="mb-4">
    <h1 class="h4 mb-1"><?= esc($ride['title'] !== null && $ride['title'] !== '' ? $ride['title'] : 'Untitled ride') ?></h1>
    <?php
    $rideTypeLabel = match ($ride['ride_type'] ?? 'leisure') {
        'race'     => 'Race',
        'commute'  => 'Commute',
        'training' => 'Training',
        'indoor'   => 'Indoor / Turbo',
        'sportive' => 'Sportive / Charity Ride',
        default    => 'Leisure',
    };
    ?>
    <span class="badge text-bg-secondary"><?= esc($rideTypeLabel) ?></span>
    <?php if ($bikeName): ?>
        <span class="text-secondary small ms-2"><i class="bi bi-bicycle" aria-hidden="true"></i> <?= esc($bikeName) ?></span>
    <?php endif; ?>
</div>

<?php if (! empty($ride['notes'])): ?>
    <p class="mb-4"><?= nl2br(esc($ride['notes'])) ?></p>
<?php endif; ?>

<!-- Stats -->
<div class="card mb-4">
    <div class="card-header py-3">
        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-bar-chart-fill me-2 text-secondary" aria-hidden="true"></i>Stats</h2>
    </div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-6 col-md-4">
                <div class="text-secondary">Distance</div>
                <div class="fw-medium"><?= number_format((float) $ride['distance_km'], 1) ?> km</div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-secondary">Moving Time</div>
                <div class="fw-medium"><?= esc(ride_format_duration($ride['moving_time_seconds'] ?? null)) ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-secondary">Elapsed Time</div>
                <div class="fw-medium"><?= esc(ride_format_duration($ride['elapsed_time_seconds'] ?? null)) ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-secondary">Elevation Gain</div>
                <div class="fw-medium"><?= $ride['elevation_gain_m'] !== null ? number_format((float) $ride['elevation_gain_m'], 0) . ' m' : '&mdash;' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-secondary">Avg Speed</div>
                <div class="fw-medium"><?= $ride['avg_speed_kmh'] !== null ? number_format((float) $ride['avg_speed_kmh'], 1) . ' km/h' : '&mdash;' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-secondary">Max Speed</div>
                <div class="fw-medium"><?= $ride['max_speed_kmh'] !== null ? number_format((float) $ride['max_speed_kmh'], 1) . ' km/h' : '&mdash;' ?></div>
            </div>
            <div class="col-6 col-md-4">
                <div class="text-secondary">Started</div>
                <div class="fw-medium"><?= $ride['started_at'] ? esc(date('j M Y, H:i', strtotime($ride['started_at']))) : '&mdash;' ?></div>
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
    </div>
</div>

<!-- Route map -->
<?php if (! empty($ride['trackpoints_json'])): ?>
<div class="card mb-4">
    <div class="card-header py-3">
        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-geo-alt-fill me-2 text-secondary" aria-hidden="true"></i>Route Map</h2>
    </div>
    <div class="card-body">
        <div id="ride-map" style="height: 400px; border-radius: var(--bs-border-radius);"></div>
        <div class="mt-3" style="height: 160px;">
            <canvas id="ride-elevation-chart"></canvas>
        </div>
        <script type="application/json" id="ride-trackpoints"><?= $ride['trackpoints_json'] ?></script>
    </div>
</div>
<?php endif; ?>

<!-- Photos -->
<?php if ($photos !== []): ?>
<div class="card">
    <div class="card-header py-3">
        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-images me-2 text-secondary" aria-hidden="true"></i>Photos</h2>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <?php foreach ($photos as $photo): ?>
                <div class="col-4 col-md-3">
                    <button
                        type="button"
                        class="btn p-0 border-0 w-100 btn-photo-open"
                        data-full-src="<?= site_url('uploads/rides/media/' . $photo['file_name']) ?>"
                        aria-label="View photo full size"
                    >
                        <img
                            src="<?= site_url('uploads/rides/media/' . $photo['file_name']) ?>"
                            alt=""
                            class="img-fluid rounded"
                            style="aspect-ratio: 1 / 1; object-fit: cover; width: 100%;"
                            loading="lazy"
                            decoding="async"
                        >
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Photo modal -->
<div class="modal fade" id="photo-modal" tabindex="-1" aria-labelledby="photo-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-header border-0">
                <h2 class="modal-title visually-hidden" id="photo-modal-label">Photo</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img id="photo-modal-img" src="" alt="" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
