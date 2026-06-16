<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

    <h1 class="mb-4">Contact</h1>

    <div class="card">
        <div class="card-body">
        <?php if (! session()->getFlashdata('success') && ! session()->getFlashdata('error') && ! session()->getFlashdata('errors')): ?>
        <p>Have a question or want to work together? Drop me a message.</p>
        <?php endif; ?>

        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert border-success d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0"></i>
            <div><?= esc(session()->getFlashdata('success')) ?></div>
        </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert border-danger d-flex align-items-start gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <div><?= esc(session()->getFlashdata('error')) ?></div>
        </div>
        <?php endif; ?>

        <?php $validationErrors = session()->getFlashdata('errors') ?? []; ?>
        <?php if ($validationErrors): ?>
        <div class="alert border-danger mb-4" role="alert">
            <div class="d-flex align-items-start gap-2 mb-1">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                <strong>Please fix the following:</strong>
            </div>
            <ul class="mb-0 ps-4 mt-2">
                <?php foreach ($validationErrors as $error): ?>
                <li><?= esc($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="/contact/send" method="post" novalidate>
            <?= csrf_field() ?>

            <!-- Anti-bot honeypot: must remain blank -->
            <div aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;">
                <label for="website">Leave this field blank</label>
                <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
            </div>

            <div class="mb-4">
                <label for="name" class="form-label fw-semibold">
                    <i class="bi bi-person-fill me-1 text-primary"></i> Name
                </label>
                <input type="text"
                    id="name"
                    name="name"
                    class="form-control"
                    value="<?= esc(old('name')) ?>"
                    placeholder="Your name"
                    maxlength="100"
                    required>
            </div>

            <div class="mb-4">
                <label for="email" class="form-label fw-semibold">
                    <i class="bi bi-envelope-fill me-1 text-primary"></i> Email
                </label>
                <input type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?= esc(old('email')) ?>"
                    placeholder="your@email.com"
                    maxlength="254"
                    required>
            </div>

            <div class="mb-4">
                <label for="message" class="form-label fw-semibold">
                    <i class="bi bi-chat-text-fill me-1 text-primary"></i> Message
                </label>
                <textarea id="message"
                        name="message"
                        class="form-control"
                        rows="7"
                        placeholder="Your message..."
                        maxlength="2000"
                        required><?= esc(old('message')) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-send-fill me-2"></i> Send Message
            </button>
        </form>
        </div>
    </div>

    <?php if (config('Mastodon')->profile): ?>
    <div class="card mt-5">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-mastodon fs-4"></i>
                <h2 class="h5 mb-0 fw-bold">Mastodon</h2>
            </div>
            <p class="small mb-3">
                You can also reach me on Mastodon. Feel free to send me a message or mention me there.
            </p>
            <a href="<?= esc(config('Mastodon')->profile) ?>"
               class="btn btn-outline-primary w-100"
               target="_blank"
               rel="noopener noreferrer me">
                <i class="bi bi-mastodon me-2"></i> Find Me on Mastodon
            </a>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($discord_url): ?>
    <div class="card mt-5">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-discord fs-4"></i>
                <h2 class="h5 mb-0 fw-bold">Discord</h2>
            </div>
            <p class="small mb-3">
                Prefer a more informal chat? Join my personal Discord server and say hi! Everyone's welcome, whether you have a question, want to collaborate, or just want to chat about tech, gaming, or anything else.
            </p>
            <a href="<?= esc($discord_url) ?>"
               class="btn btn-outline-primary w-100"
               target="_blank"
               rel="noopener noreferrer">
                <i class="bi bi-discord me-2"></i> Join My Server
            </a>
        </div>
    </div>
    <?php endif; ?>

<?= $this->endSection() ?>
