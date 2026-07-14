<?php if ($rides !== []): ?>
    <?php foreach ($rides as $ride): ?>
        <?php
        $cover = $coverPhotos[$ride['id']] ?? null;
        $rideTypeLabel = match ($ride['ride_type'] ?? 'leisure') {
            'race'     => 'Race',
            'commute'  => 'Commute',
            'training' => 'Training',
            'indoor'   => 'Indoor / Turbo',
            'sportive' => 'Sportive / Charity Ride',
            default    => 'Leisure',
        };
        $bikeName = $ride['bike_id'] && isset($bikeNames[$ride['bike_id']]) ? $bikeNames[$ride['bike_id']] : null;
        ?>
        <div class="col">
            <a href="<?= site_url('rides/' . $ride['id']) ?>" class="text-decoration-none text-body">
                <div class="card h-100">
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
                        <h2 class="h6 mb-1 fw-semibold">
                            <?= esc($ride['title'] !== null && $ride['title'] !== '' ? $ride['title'] : 'Untitled ride') ?>
                        </h2>
                        <div class="mb-2">
                            <span class="badge text-bg-secondary"><?= esc($rideTypeLabel) ?></span>
                        </div>
                        <div class="small text-secondary">
                            <?= $ride['started_at'] ? esc(date('j M Y', strtotime($ride['started_at']))) : '&mdash;' ?>
                        </div>
                        <div class="small text-secondary d-flex align-items-center gap-1 mt-1">
                            <i class="bi bi-signpost-2" aria-hidden="true"></i>
                            <?= number_format((float) $ride['distance_km'], 1) ?> km
                            <?php if ($bikeName): ?>
                                <span class="mx-1">&middot;</span>
                                <i class="bi bi-bicycle" aria-hidden="true"></i>
                                <?= esc($bikeName) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-secondary py-3">No rides yet.</p>
<?php endif; ?>
