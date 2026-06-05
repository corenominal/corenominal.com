<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <img src="/assets/img/skull-not-found.svg" alt="" class="img-fluid mb-5 invert-light" aria-hidden="true">
    <h1>404 Not Found</h1>
    <p>The resource you are looking for does not exist.</p>

    <a href="/" class="btn btn-primary">Return to Home</a>

<?= $this->endSection() ?>