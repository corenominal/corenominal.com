<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a class="text-decoration-none" href="<?= site_url('blog') ?>">Blog</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                #<?= esc((string) $tagName) ?>
            </li>
        </ol>
    </nav>

    <h2 class="h6 text-uppercase text-muted fw-semibold mb-3">Posts tagged &ldquo;<?= esc((string) $tagName) ?>&rdquo;</h2>
    <ol class="list-unstyled mb-5">
        <?php foreach ($posts as $index => $post): ?>
        <li class="h-entry d-flex align-items-baseline gap-3 py-3<?= $index < count($posts) - 1 ? ' border-bottom' : '' ?>">
            <time class="text-muted small text-nowrap flex-shrink-0 dt-published" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($post['published_at']))) ?>">
                <?= esc(date('j M Y', strtotime($post['published_at']))) ?>
            </time>
            <div>
                <a class="fw-semibold text-body text-decoration-none p-name u-url" href="<?= site_url('blog/posts/' . esc($post['slug'])) ?>">
                    <?= esc(html_entity_decode($post['title_html'] ?? $post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                </a>
                <?php if (!empty($post['excerpt'])): ?>
                <p class="text-muted small mb-0 mt-1 p-summary">
                    <?= esc($post['excerpt']) ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($post['tags_list'])): ?>
                <div class="d-flex flex-wrap gap-1 mt-2">
                    <?php foreach ($post['tags_list'] as $tag): ?>
                    <a class="badge bg-secondary text-decoration-none p-category" href="<?= site_url('blog/tags/' . esc($tag['slug'])) ?>">
                        <?= esc($tag['tag']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ol>

<?= $this->endSection() ?>
