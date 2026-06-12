<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= esc((string) $backUrl) ?>" class="text-decoration-none text-secondary small">&larr; <?= esc((string) $backLabel) ?></a>
</div>

<article>

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

    <div class="d-flex align-items-start gap-2 mb-2">
        <?php if (! empty($bookmark['favicon'])): ?>
            <img
                src="<?= esc($bookmark['favicon']) ?>"
                alt=""
                width="20"
                height="20"
                class="flex-shrink-0 mt-1"
                loading="lazy"
                decoding="async"
                aria-hidden="true"
            >
        <?php endif; ?>
        <h1 class="h5 mb-0 fw-semibold">
            <a href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-body text-decoration-none">
                <?= esc($bookmark['title']) ?>
            </a>
        </h1>
    </div>

    <?php if (! empty($bookmark['url'])): ?>
        <p class="small text-secondary mb-3">
            <a href="<?= esc($bookmark['url']) ?>" target="_blank" rel="noopener noreferrer" class="text-secondary">
                <?= esc($bookmark['url']) ?>
            </a>
        </p>
    <?php endif; ?>

    <?php if (! empty($bookmark['notes_html'])): ?>
        <div class="mb-3 small">
            <?= $bookmark['notes_html'] ?>
        </div>
    <?php endif; ?>

    <?php if (! empty($bookmark['tags'])): ?>
        <div class="mb-3 d-flex flex-wrap gap-1" aria-label="Tags">
            <?php foreach (array_filter(array_map('trim', explode(',', $bookmark['tags']))) as $tag): ?>
                <a
                    class="badge text-bg-secondary text-decoration-none fw-normal"
                    href="<?= site_url('bookmarks') . '?q=' . urlencode($tag) ?>"
                ><?= esc($tag) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex align-items-center gap-2">
        <time class="small text-secondary" datetime="<?= esc((string) ($bookmark['created_at'] ?? '')) ?>">
            <?= esc(date('j M Y, H:i', strtotime((string) ($bookmark['created_at'] ?? 'now')))) ?>
        </time>
        <?php if (user_in_group('administrators') && ! empty($bookmark['private'])): ?>
            <span class="badge text-bg-secondary">Private</span>
        <?php endif; ?>
    </div>

</article>

<?= $this->endSection() ?>
