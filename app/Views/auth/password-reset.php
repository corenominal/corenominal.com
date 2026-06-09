<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
    <form>
        <h1 class="h3 mb-3">Reset your password</h1>
        <p class="mb-3">Enter your email address and <?= config('Voice')->subject ?>&rsquo;ll send you a link to set a new password.</p>
        <div class="form-floating">
            <input type="email" class="form-control" id="floatingEmail" placeholder="name@example.com" required autocomplete="email" />
            <label for="floatingEmail">Email address</label>
        </div>
        <input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
        <button class="btn btn-primary py-2 mt-3" type="submit">Send reset link</button>
    </form>

    <p class="mt-3 mb-1"><a href="/auth">Back to login</a></p>

<?= $this->endSection() ?>