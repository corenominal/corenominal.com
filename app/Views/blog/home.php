<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

    <form class="search-form mb-4" action="<?= site_url('blog/search') ?>" method="get" role="search">
        <div class="input-group">
            <input
                class="form-control"
                type="search"
                name="q"
                placeholder="Search posts&hellip;"
                aria-label="Search posts"
                required
            >
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span class="visually-hidden">Search</span>
            </button>
        </div>
    </form>

    <?php if ($latestPost): ?>
    <article class="h-entry mt-3">
        <header class="mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="badge bg-primary text-uppercase">Latest post</span>
                <time class="text-muted small dt-published" datetime="<?= esc(date('Y-m-d\TH:i:sP', strtotime($latestPost['published_at']))) ?>">
                    <?= esc(date('j F Y', strtotime($latestPost['published_at']))) ?>
                </time>
            </div>
            <h1 class="display-5 fw-bold lh-sm mb-0">
                <a class="p-name u-url text-body text-decoration-none" href="<?= site_url('blog/posts/' . esc($latestPost['slug'])) ?>">
                    <?= esc(html_entity_decode($latestPost['title_html'] ?? $latestPost['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>
                </a>
            </h1>
        </header>
        <?php if (!empty($latestPost['post_video'])): ?>
        <div class="ratio ratio-16x9 mb-4 rounded">
            <video controls class="w-100 h-100 object-fit-cover rounded">
                <source src="<?= base_url('uploads/blog/media/' . esc($latestPost['post_video'])) ?>">
            </video>
        </div>
        <?php endif; ?>
        <div class="post__body e-content">
            <?= $latestPost['body_html'] ?>
        </div>
        <div class="mt-4 pt-3 border-top">
            <span class="text-muted small me-2">View as:</span>
            <a class="small text-decoration-none me-3" href="<?= site_url('blog/posts/' . esc($latestPost['slug']) . '/json') ?>"><i class="bi bi-filetype-json me-1"></i>JSON</a>
            <a class="small text-decoration-none" href="<?= site_url('blog/posts/' . esc($latestPost['slug']) . '/markdown') ?>"><i class="bi bi-markdown me-1"></i>Markdown</a>
        </div>
        <?php if (!empty($latestPost['tags_list'])): ?>
        <footer class="mt-4">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($latestPost['tags_list'] as $tag): ?>
                <a class="badge bg-secondary text-decoration-none p-category" href="<?= site_url('blog/tags/' . esc($tag['slug'])) ?>">
                    <?= esc($tag['tag']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </footer>
        <?php endif; ?>
    </article>

    <a href="<?= site_url('blog/feed/rss') ?>" class="mb-0 btn btn-outline-primary mt-4 w-100"><i class="bi bi-rss me-2"></i>If you enjoyed this post or found it useful, you can subscribe to my RSS feed</a>
    <?php endif; ?>

    <?php if (!empty($otherPosts)): ?>
    <div class="mt-5 mb-5">
        <h2 class="h6 text-uppercase text-muted fw-semibold mb-3">More posts</h2>
        <ol id="post-list" class="list-unstyled mb-0">
            <?php foreach ($otherPosts as $post): ?>
            <li class="h-entry d-flex align-items-baseline gap-3 py-3 border-bottom">
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
        <?php if (!empty($hasMorePosts)): ?>
        <div
            id="post-list-sentinel"
            class="py-4 text-center"
            data-offset="<?= count($otherPosts) ?>"
            data-url="<?= site_url('blog/posts') ?>"
        >
            <div class="spinner-border spinner-border-sm text-secondary" role="status">
                <span class="visually-hidden">Loading&hellip;</span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<!-- Image preview modal -->
<div class="modal fade" id="post-image-modal" tabindex="-1" aria-labelledby="post-image-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-6" id="post-image-modal-label">Image Preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div id="post-image-modal-img-wrap" class="mx-auto">
                    <img id="post-image-modal-img" src="" alt="" class="img-fluid rounded" style="max-height: 80vh;">
                </div>
                <p id="post-image-modal-caption" class="text-secondary small mt-2 mb-0"></p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
