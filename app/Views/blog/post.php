<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a class="text-decoration-none" href="<?= site_url('blog') ?>">Blog</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= esc($post['title']) ?>
            </li>
        </ol>
    </nav>

    <article class="h-entry">
        <header class="mb-4">
            <time class="text-muted small dt-published d-block mb-3" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($post['published_at']))) ?>">
                <?= esc(date('j F Y', strtotime($post['published_at']))) ?>
            </time>
            <h1 class="display-5 fw-bold lh-sm mb-0 p-name">
                <?= esc(html_entity_decode($post['title_html'] ?? $post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
            </h1>
        </header>
        <?php if (!empty($post['post_video'])): ?>
        <div class="ratio ratio-16x9 mb-4 rounded">
            <video controls class="w-100 h-100 object-fit-cover rounded">
                <source src="<?= base_url('uploads/blog/media/' . esc($post['post_video'])) ?>">
            </video>
        </div>
        <?php endif; ?>
        <?php if ((time() - strtotime($post['published_at'])) > 365 * 24 * 60 * 60): ?>
        <div class="alert alert-primary mb-4" role="alert">
            <strong><i class="bi bi-exclamation-triangle-fill me-2"></i> Heads up:</strong> This post is over a year old and may be out of date.
        </div>
        <?php endif; ?>
        <div class="post__body e-content">
            <?= $post['body_html'] ?>
        </div>
        <div class="mt-4 pt-3 border-top">
            <span class="text-muted small me-2">View as:</span>
            <a class="small text-decoration-none me-3" href="<?= site_url('blog/posts/' . esc($post['slug']) . '/json') ?>"><i class="bi bi-filetype-json me-1"></i>JSON</a>
            <a class="small text-decoration-none" href="<?= site_url('blog/posts/' . esc($post['slug']) . '/markdown') ?>"><i class="bi bi-markdown me-1"></i>Markdown</a>
        </div>
        <?php if (!empty($post['tags_list'])): ?>
        <footer class="mt-4">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($post['tags_list'] as $tag): ?>
                <a class="badge bg-secondary text-decoration-none p-category" href="<?= site_url('blog/tags/' . esc($tag['slug'])) ?>">
                    <?= esc($tag['tag']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </footer>
        <?php endif; ?>
    </article>

    <a href="<?= site_url('blog/feed/rss') ?>" class="mb-0 btn btn-outline-primary mt-4 w-100"><i class="bi bi-rss me-2"></i>If you enjoyed this post or found it useful, you can subscribe to my RSS feed</a>

    <?php if (!empty($similarPosts)): ?>
    <div class="mt-5 mb-5">
        <h2 class="h6 text-uppercase text-muted fw-semibold mb-3"><?= esc((string) $similarHeading) ?></h2>
        <ol class="list-unstyled mb-0">
            <?php foreach ($similarPosts as $index => $related): ?>
            <li class="h-entry d-flex align-items-baseline gap-3 py-3<?= $index < count($similarPosts) - 1 ? ' border-bottom' : '' ?>">
                <time class="text-muted small text-nowrap flex-shrink-0 dt-published" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($related['published_at']))) ?>">
                    <?= esc(date('j M Y', strtotime($related['published_at']))) ?>
                </time>
                <div>
                    <a class="fw-semibold text-body text-decoration-none p-name u-url" href="<?= site_url('blog/posts/' . esc($related['slug'])) ?>">
                        <?= esc(html_entity_decode($related['title_html'] ?? $related['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                    </a>
                    <?php if (!empty($related['excerpt'])): ?>
                    <p class="text-muted small mb-0 mt-1 p-summary">
                        <?= esc($related['excerpt']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($related['tags_list'])): ?>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <?php foreach ($related['tags_list'] as $tag): ?>
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
    </div>
    <?php endif; ?>

<?= $this->endSection() ?>
