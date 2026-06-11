<?php if ($statuses !== []): ?>
    <?php foreach ($statuses as $status): ?>
        <article
            class="py-5"
            data-status-id="<?= (int) $status['id'] ?>"
            <?php if (user_in_group('administrators')): ?>
            data-status-content="<?= esc($status['content'] ?? '') ?>"
            data-status-media="<?= esc(json_encode(array_map(fn($m) => [
                'id'          => (int) $m['id'],
                'description' => (string) ($m['description'] ?? ''),
                'url'         => (string) ($m['url'] ?? ''),
                'mime_type'   => (string) ($m['mimeType'] ?? $m['mime_type'] ?? ''),
            ], $status['media'] ?? []), JSON_UNESCAPED_SLASHES)) ?>"
            <?php endif; ?>
        >
            <div>
                <div class="flex-grow-1 min-w-0">

                    <div class="mb-2">
                        <?= $status['content_html'] ?>
                    </div>

                    <?php if (! empty($status['media'])): ?>
                        <div class="d-flex flex-wrap gap-2 mb-2 <?= count($status['media']) === 1 ? '' : '' ?>">
                            <?php foreach ($status['media'] as $media): ?>
                                <?php $mimeType = (string) ($media['mimeType'] ?? $media['mime_type'] ?? '') ?>
                                <figure class="m-0">
                                    <?php if ($mimeType === 'video/mp4'): ?>
                                        <video
                                            class="rounded img-fluid"
                                            style="max-height: 320px;"
                                            src="<?= esc($media['url']) ?>"
                                            controls
                                            preload="metadata"
                                            aria-label="<?= esc(! empty($media['description']) ? $media['description'] : 'Status video') ?>"
                                        ></video>
                                    <?php else: ?>
                                        <img
                                            class="rounded img-fluid status-media-img"
                                            style="cursor: pointer;"
                                            src="<?= esc($media['url']) ?>"
                                            alt="<?= esc(! empty($media['description']) ? $media['description'] : 'Status media') ?>"
                                            loading="lazy"
                                            decoding="async"
                                            <?php if (! empty($media['width']) && ! empty($media['height'])): ?>
                                                data-width="<?= (int) $media['width'] ?>"
                                                data-height="<?= (int) $media['height'] ?>"
                                            <?php endif; ?>
                                            <?php if (! empty($media['description'])): ?>
                                                data-caption="<?= esc($media['description']) ?>"
                                            <?php endif; ?>
                                        >
                                    <?php endif; ?>
                                    <?php if (! empty($media['description'])): ?>
                                        <figcaption class="text-secondary small mt-1"><?= esc($media['description']) ?></figcaption>
                                    <?php endif; ?>
                                </figure>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex align-items-center gap-2 mt-1">
                        <time class="text-secondary small" datetime="<?= esc((string) ($status['created_at'] ?? '')) ?>">
                            <a class="text-secondary text-decoration-none" href="/status/<?= esc($status['uuid']) ?>"><?= esc(date('j M Y, H:i', strtotime((string) ($status['created_at'] ?? 'now')))) ?></a>
                        </time>
                        <?php if (! empty($status['mastodon_url'])): ?>
                            <a href="<?= esc($status['mastodon_url']) ?>" class="text-secondary small text-decoration-none" target="_blank" rel="noopener noreferrer" aria-label="View on Mastodon">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (user_in_group('administrators')): ?>
                            <button
                                type="button"
                                class="btn btn-sm btn-link text-secondary p-0 lh-1 status-edit-btn"
                                data-status-id="<?= (int) $status['id'] ?>"
                                aria-label="Edit status"
                            ><i class="bi bi-pencil" aria-hidden="true"></i></button>
                            <button
                                type="button"
                                class="btn btn-sm btn-link text-secondary p-0 lh-1 status-delete-btn"
                                data-status-id="<?= (int) $status['id'] ?>"
                                aria-label="Delete status"
                            ><i class="bi bi-trash" aria-hidden="true"></i></button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </article>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-secondary py-3">No statuses yet.</p>
<?php endif; ?>
