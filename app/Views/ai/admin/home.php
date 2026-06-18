<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

<div class="mb-4 pb-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0">AI Chat</h1>
        <p class="text-secondary mb-0 mt-1">Ollama-powered chat sessions.</p>
    </div>
    <a href="/admin/ai/chat" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-chat-dots me-1"></i>Open Chat
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-semibold mb-1">Sessions</div>
                        <div class="fs-2 fw-bold"><?= $stats['sessions'] ?></div>
                    </div>
                    <i class="bi bi-chat-dots-fill fs-3 text-secondary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-semibold mb-1">Messages</div>
                        <div class="fs-2 fw-bold"><?= $stats['messages'] ?></div>
                    </div>
                    <i class="bi bi-chat-left-text-fill fs-3 text-secondary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-secondary small text-uppercase fw-semibold mb-1">Pinned</div>
                        <div class="fs-2 fw-bold"><?= $stats['pinned'] ?></div>
                    </div>
                    <i class="bi bi-pin-fill fs-3 text-secondary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <div class="text-secondary small text text-uppercase fw-semibold mb-1">Avg / Session</div>
                        <div class="fs-2 fw-bold"><?= $stats['avg'] ?></div>
                    </div>
                    <i class="bi bi-bar-chart-fill fs-3 text-secondary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent sessions -->
<div class="card">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2"></i>Recent Sessions</span>
        <a href="/admin/ai/chat" class="btn btn-sm btn-outline-primary"><i class="bi bi-chat-dots me-1"></i>Open Chat</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_sessions)): ?>
        <div class="text-center text-secondary py-5">
            <i class="bi bi-chat-dots fs-1 d-block mb-2 opacity-25"></i>
            No chat sessions yet.
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($recent_sessions as $session): ?>
            <li class="list-group-item d-flex align-items-center gap-3 py-2">
                <i class="bi bi-chat-dots text-secondary flex-shrink-0"></i>
                <div class="flex-grow-1 text-truncate">
                    <a href="/admin/ai/chat/<?= esc($session['uuid']) ?>" class="text-decoration-none text-body">
                        <?= esc($session['title'] ?? 'Untitled Session') ?>
                    </a>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <?php if (!empty($session['model'])): ?>
                    <span class="badge text-bg-secondary font-monospace" style="font-size:0.7em"><?= esc($session['model']) ?></span>
                    <?php endif; ?>
                    <?php if ($session['pinned']): ?>
                    <i class="bi bi-pin-fill text-warning" title="Pinned"></i>
                    <?php endif; ?>
                    <span class="text-secondary small"><?= date('d M y', strtotime($session['created_at'])) ?></span>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
