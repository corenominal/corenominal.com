<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <img src="/icon.svg" alt="corenominal" class="img-152 mb-5 invert" aria-hidden="true">
    <h1 class="h3 text-uppercase">Logout</h1>
    <p>Your session has been terminated.</p>
    <a href="<?= site_url() ?>" class="btn btn-primary mt-3">Return to Home</a>

<?= $this->endSection() ?>