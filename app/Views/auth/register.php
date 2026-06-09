<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <?php if (! empty($registrationDisabled)): ?>

        <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
        <h1>Registration Disabled</h1>
        <p>New user registration is currently disabled. Please check back later or contact the site administrator if you need an account.</p>

        <p class="mt-3 mb-1">Already have an account? <a href="/auth">Login here.</a></p>

    <?php else: ?>

        <form>
            <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
            <h1>Create Account</h1>
            <div class="form-floating mb-2">
                <input type="email" class="form-control" id="floatingEmail" placeholder="name@example.com" required />
                <label for="floatingEmail">Email address</label>
            </div>
            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="floatingUsername" placeholder="username" required autocomplete="username" />
                <label for="floatingUsername">Username</label>
            </div>
            <div class="form-floating mb-2">
                <input type="text" class="form-control" id="floatingRealname" placeholder="Your name" autocomplete="name" />
                <label for="floatingRealname">Full name (optional)</label>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="floatingPassword" placeholder="Password" required autocomplete="new-password" />
                <label for="floatingPassword">Password</label>
            </div>
            <div id="password-requirements" aria-live="polite">
                <small data-req="length"  class="req-item">At least 12 characters</small>
                <small data-req="upper"   class="req-item">At least one uppercase letter</small>
                <small data-req="lower"   class="req-item">At least one lowercase letter</small>
                <small data-req="number"  class="req-item">At least one number</small>
                <small data-req="special" class="req-item">At least one special character</small>
            </div>
            <div class="form-floating mb-2">
                <input type="password" class="form-control" id="floatingPasswordConfirm" placeholder="Confirm password" required autocomplete="new-password" />
                <label for="floatingPasswordConfirm">Confirm password</label>
            </div>
            <input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <button class="btn btn-primary w-100 py-2" type="submit">Register</button>

            <p class="mt-3 mb-1">Already have an account? <a href="/auth">Login here.</a></p>

        </form>

    <?php endif; ?>

<?= $this->endSection() ?>