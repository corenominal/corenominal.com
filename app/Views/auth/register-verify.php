<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
    <h1>Check Your Email</h1>
    <p class="text-body-secondary"><?= config('Voice')->subject ?>&rsquo;ve sent a verification link to your email address. Please click the link to activate your account.</p>
    
    <p class="text-body-secondary">To ensure you receive <?= config('Voice')->possessive ?> emails, please whitelist <strong><?= config('Email')->fromEmail ?></strong> in your email client.</p>

    <p class="text-body-secondary">Don&rsquo;t forget to check your <strong>spam or junk folder</strong> if you don&rsquo;t see the email within a few minutes.</p>

    <a href="/auth" class="btn btn-primary w-100 py-2 mt-2">Back to Login</a>

<?= $this->endSection() ?>
