<?php if ($bookmarks !== []): ?>
    <?php foreach ($bookmarks as $bookmark): ?>
        <article class="mb-4 border border rounded p-3" data-bookmark-id="<?= (int) $bookmark['id'] ?>">

            <?php if (! empty($bookmark['image'])): ?>
                <div class="mb-3 rounded overflow-hidden">
                    <?php if (! empty($bookmark['url'])): ?>
                        <a href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer">
                    <?php endif; ?>
                    <img
                        src="/uploads/bookmarks/media/<?= esc($bookmark['image']) ?>"
                        alt="<?= esc($bookmark['title']) ?>"
                        class="img-fluid"
                        loading="lazy"
                        decoding="async"
                    >
                    <?php if (! empty($bookmark['url'])): ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-start gap-2 mb-1">
                <?php if (! empty($bookmark['favicon'])): ?>
                    <img
                        src="<?= esc($bookmark['favicon']) ?>"
                        alt=""
                        width="16"
                        height="16"
                        class="flex-shrink-0 mt-1"
                        loading="lazy"
                        decoding="async"
                        aria-hidden="true"
                    >
                <?php endif; ?>
                <h2 class="h6 mb-0 fw-semibold">
                    <a href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-body text-decoration-none">
                        <?= esc($bookmark['title']) ?>
                    </a>
                </h2>
            </div>

            <?php if (! empty($bookmark['notes_html'])): ?>
                <div class="mt-2 small text-body-secondary">
                    <?= $bookmark['notes_html'] ?>
                </div>
            <?php endif; ?>

            <?php if (! empty($bookmark['tags'])): ?>
                <div class="mt-2 d-flex flex-wrap gap-1" aria-label="Tags">
                    <?php foreach (array_filter(array_map('trim', explode(',', $bookmark['tags']))) as $tag): ?>
                        <a
                            class="badge text-bg-secondary text-decoration-none fw-normal"
                            href="<?= site_url('bookmarks') . '?q=' . urlencode($tag) ?>"
                        ><?= esc($tag) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-2 d-flex align-items-center gap-2">
                <time class="small text-secondary" datetime="<?= esc((string) ($bookmark['created_at'] ?? '')) ?>">
                    <a class="text-secondary text-decoration-none" href="<?= site_url('bookmarks/' . $bookmark['uuid']) ?>">
                        <?= esc(date('j M Y', strtotime((string) ($bookmark['created_at'] ?? 'now')))) ?>
                    </a>
                </time>
                <?php if (user_in_group('administrators') && ! empty($bookmark['private'])): ?>
                    <span class="badge text-bg-secondary">Private</span>
                <?php endif; ?>
            </div>

        </article>
    <?php endforeach; ?>
<?php else: ?>
    <p class="text-secondary py-3">No bookmarks found.</p>
<?php endif; ?>
