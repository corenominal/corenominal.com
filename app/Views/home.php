<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <h1 class="mb-4"><span aria-hidden="true">~/ </span>corenominal</h1>

    <p class="lead">Hello, World! Welcome to my website. I'm a tech enthusiast and web developer. When I'm not sat in front of my computer, I can be found reading Warhammer 40,000 fiction, performing grumpa duties, listening to tech podcasts, or riding my bike.</p>

    <p>I occasionally write about technology, programming, and my personal projects on my <a href="/blog">blog</a>. My latest blog post is titled <strong>"<a href="/blog/my-latest-post">Exploring AI tooling, model runners, cloud gateways, and local setups</a>"</strong>.</p>

    <?php if (isset($status) && $status !== null): ?>
        <p class="mb-3">I write short updates about my life and work and syndicate them to my <strong><a href="<?= esc(config('Mastodon')->profile) ?>" target="_blank" rel="noopener noreferrer">Mastodon profile</a></strong>. The latest update is below:</p>
        <div id="timeline-items">
            <?= view('status/partials/timeline_items', [
                'statuses'        => [$status],
                'mastodonHandle'  => $mastodonHandle ?? '',
                'mastodonProfile' => $mastodonProfile ?? '',
            ]) ?>
        </div>
        <div class="d-flex flex-column flex-lg-row gap-3 mt-3 mb-5">
            <a class="btn btn-outline-primary w-100 w-lg-50" href="/status">
                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>View all status updates
            </a>
            <a class="btn btn-outline-primary w-100 w-lg-50" href="<?= esc(config('Mastodon')->profile) ?>" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-mastodon me-1" aria-hidden="true"></i>Follow me on Mastodon
            </a>
        </div>
    <?php endif; ?>

    <p class="mb-3">I also keep a collection of bookmarks to interesting articles, projects, and resources that I come across. You can check them out on my <a href="/bookmarks">bookmarks page</a>. The latest bookmark is below:</p>

    <?php if (isset($latestBookmark) && $latestBookmark !== null): ?>
        <?= view('bookmarks/partials/bookmark_items', ['bookmarks' => [$latestBookmark]]) ?>
        <div class="d-flex flex-column flex-lg-row gap-3 mt-3 mb-5">
            <a class="btn btn-outline-primary w-100 w-lg-50" href="/bookmarks">
                <i class="bi bi-arrow-right-circle me-1" aria-hidden="true"></i>View all bookmarks
            </a>
        </div>
    <?php endif; ?>

    <p>I publish my open source projects on <a href="https://github.com/corenominal" target="_blank" rel="noopener noreferrer">GitHub</a>.</p>

<!-- Image preview modal -->
<div class="modal fade" id="timeline-image-modal" tabindex="-1" aria-labelledby="timeline-image-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h2 class="modal-title fs-6" id="timeline-image-modal-label">Image Preview</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <div id="timeline-image-modal-img-wrap" class="mx-auto">
                    <img id="timeline-image-modal-img" src="" alt="" class="img-fluid rounded" style="max-height: 80vh;">
                </div>
                <p id="timeline-image-modal-caption" class="text-secondary small mt-2 mb-0"></p>
            </div>
        </div>
    </div>
</div>

<?php if (user_in_group('administrators')): ?>
<!-- Delete confirmation modal -->
<div class="modal fade" id="delete-status-modal" tabindex="-1" aria-labelledby="delete-status-modal-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="delete-status-modal-label">Delete status</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this status? This cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="delete-status-confirm-btn">Delete</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>