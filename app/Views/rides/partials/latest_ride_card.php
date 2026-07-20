<?php if ($ride !== null): ?>
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
    <a href="<?= site_url('rides/' . $ride['id']) ?>" class="text-decoration-none text-body">
        <div class="card">
            <?php if ($cover): ?>
                <img
                    src="<?= site_url('uploads/rides/media/' . $cover) ?>"
                    alt=""
                    class="card-img-top"
                    style="aspect-ratio: 16 / 9; object-fit: cover;"
                    loading="lazy"
                    decoding="async"
                >
            <?php else: ?>
                <div
                    class="d-flex align-items-center justify-content-center bg-body-secondary text-secondary card-img-top"
                    style="aspect-ratio: 16 / 9;"
                >
                    <i class="bi bi-map fs-1" aria-hidden="true"></i>
                </div>
            <?php endif; ?>
            <div class="card-body">
                <h3 class="h6 mb-1 fw-semibold">
                    <?= esc($ride['title'] !== null && $ride['title'] !== '' ? $ride['title'] : 'Untitled ride') ?>
                </h3>
                <div class="mb-2">
                    <span class="badge text-bg-secondary"><?= esc($rideTypeLabel) ?></span>
                </div>
                <div class="small text-secondary">
                    <?= $ride['started_at'] ? esc(date('j M Y', strtotime($ride['started_at']))) : '&mdash;' ?>
                </div>
                <div class="small text-secondary d-flex flex-wrap align-items-center gap-1 mt-1">
                    <i class="bi bi-signpost-2" aria-hidden="true"></i>
                    <?= number_format((float) $ride['distance_km'], 1) ?> km
                    <span class="mx-1">&middot;</span>
                    <i class="bi bi-stopwatch" aria-hidden="true"></i>
                    <?= esc(ride_format_duration($ride['moving_time_seconds'])) ?>
                    <?php if ($ride['elevation_gain_m'] !== null): ?>
                        <span class="mx-1">&middot;</span>
                        <i class="bi bi-graph-up" aria-hidden="true"></i>
                        <?= number_format((float) $ride['elevation_gain_m']) ?> m
                    <?php endif; ?>
                    <?php if ($bikeName): ?>
                        <span class="mx-1">&middot;</span>
                        <i class="bi bi-bicycle" aria-hidden="true"></i>
                        <?= esc($bikeName) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </a>
<?php else: ?>
    <p class="text-secondary py-3">No rides yet.</p>
<?php endif; ?>
