<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <img src="/assets/img/skull-unauthorised.svg" alt="" class="img-152 mb-5 invert-light" aria-hidden="true">
    <h1>Access Denied</h1>
    <p>You do not have permission to view this page.</p>

    <a href="/" class="btn btn-primary">Return to Home</a>

<?= $this->endSection() ?>