<?= $this->extend('templates/basic-centered') ?>
<?= $this->section('content') ?>

    <?php if (!$valid): ?>
        <!-- Invalid or expired reset link -->
        <div id="error-panel">
            <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
            <h1 class="h3 mb-3 fw-normal">Link invalid</h1>
            <p><?= $message ?></p>
            <a href="/auth" class="btn btn-primary py-2 mt-2">Back to login</a>
        </div>
    <?php else: ?>
        <!-- Valid reset token — show the new password form -->
        <form>
            <img src="/assets/img/skull.svg" alt="corenominal" class="img-152 mb-5 invert-light" aria-hidden="true">
            <h1 class="h3 mb-3 fw-normal">Set a new password</h1>
            <div class="form-floating">
                <input type="password" class="form-control mb-2" id="floatingPassword" placeholder="New password" required autocomplete="new-password" />
                <label for="floatingPassword">New password</label>
            </div>
            <div id="password-requirements" aria-live="polite">
                <small data-req="length"  class="req-item">At least 12 characters</small>
                <small data-req="upper"   class="req-item">At least one uppercase letter</small>
                <small data-req="lower"   class="req-item">At least one lowercase letter</small>
                <small data-req="number"  class="req-item">At least one number</small>
                <small data-req="special" class="req-item">At least one special character</small>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control mb-2" id="floatingPasswordConfirm" placeholder="Confirm new password" required autocomplete="new-password" />
                <label for="floatingPasswordConfirm">Confirm new password</label>
            </div>
            <input type="hidden" id="reset_uuid" value="<?= $uuid ?>">
            <input type="hidden" id="csrf_token" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
            <button class="btn btn-primary w-100 py-2 mt-3" type="submit">Set new password</button>
        </form>
    <?php endif; ?>

<?= $this->endSection() ?>