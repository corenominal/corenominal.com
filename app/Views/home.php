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

    <?php if (!empty($githubHeatmap)): ?>
    <div class="github-heatmap-wrap mb-4">
        <div class="github-heatmap">
            <?php foreach ($githubHeatmap as $date => $count):
                $level = match(true) {
                    $count === 0 => 0,
                    $count <= 2  => 1,
                    $count <= 5  => 2,
                    $count <= 9  => 3,
                    default      => 4,
                };
                $label = $count === 0
                    ? 'No activity on ' . date('j M', strtotime($date))
                    : $count . ' event' . ($count === 1 ? '' : 's') . ' on ' . date('j M', strtotime($date));
            ?>
                <div class="heatmap-cell heatmap-cell--level-<?= $level ?>"
                     data-date="<?= $date ?>"
                     aria-label="<?= esc($label) ?>"></div>
            <?php endforeach; ?>
        </div>
        <p class="text-secondary small mt-1 mb-0">GitHub activity — last 56 days</p>
        <?php
        $latestDate   = array_key_first($githubActivity ?? []);
        $latestEvents = $latestDate ? ($githubActivity[$latestDate] ?? []) : [];
        ?>
        <div id="github-activity-panel" class="mt-3">
            <p class="small text-secondary mb-2" id="github-activity-date">
                <?= $latestDate ? date('l, j F', strtotime($latestDate)) : '' ?>
            </p>
            <div id="github-activity-list">
                <?php if (!empty($latestEvents)): ?>
                    <?php foreach ($latestEvents as $event): ?>
                    <a href="<?= esc($event['link']) ?>" target="_blank" rel="noopener noreferrer"
                       class="d-flex align-items-start gap-2 py-1 text-body text-decoration-none">
                        <i class="bi bi-<?= esc($event['icon']) ?> mt-1 flex-shrink-0" aria-hidden="true"></i>
                        <div class="small">
                            <span class="badge text-bg-<?= esc($event['label_class']) ?> me-1"><?= esc($event['label']) ?></span>
                            <span class="text-secondary"><?= esc($event['repo']) ?></span>
                            <div><?= $event['description'] ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="small text-secondary mb-0">No recent GitHub activity.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        $activityForJs = [];
        foreach (($githubActivity ?? []) as $d => $events) {
            $activityForJs[$d] = array_map(fn($ev) => [
                'icon'        => $ev['icon'],
                'label'       => $ev['label'],
                'label_class' => $ev['label_class'],
                'repo'        => $ev['repo'],
                'description' => $ev['description'],
                'link'        => $ev['link'],
            ], $events);
        }
        ?>
        <script>window.githubActivityData = <?= json_encode($activityForJs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    </div>
    <?php endif; ?>

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