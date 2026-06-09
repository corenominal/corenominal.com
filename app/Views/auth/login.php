<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
    <form>
        <h1 class="h3 mb-3 fw-normal text-uppercase">Login</h1>
        <div class="form-floating">
            <input type="email" class="form-control mb-2" id="floatingInput" placeholder="name@example.com" required />
            <label for="floatingInput">Email address</label>
        </div>
        <div class="form-floating">
            <input type="password" class="form-control mb-2" id="floatingPassword" placeholder="Password" required />
            <label for="floatingPassword">Password</label>
        </div>
        <div class="form-check text-start my-3">
            <input class="form-check-input" type="checkbox" value="remember-me" id="checkDefault" />
            <label class="form-check-label" for="checkDefault">
                Remember me
            </label>
        </div>
        <input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
        <button class="btn btn-primary w-100 py-2" type="submit">Login</button>
        
        <p class="mt-3 mb-1 text-body-secondary">Don't have an account? <a href="/auth/register">Register here.</a></p>
        <p class="mb-3 text-body-secondary">Forgot your password? <a href="/auth/password-reset">Reset it here.</a></p>

        <small class="mt-5 mb-3 text-body-secondary"><span class="flip-horizontal">&copy;</span> <?= date('Y') ?>.<br />All rights reserved. <br /> 
        <a href="<?= site_url() ?>">Home</a> | <a href="<?= config('Urls')->github ?>">GitHub</a>
         | <a href="<?= config('Urls')->license ?>">LICENSE</a></small>
    </form>

<?= $this->endSection() ?>