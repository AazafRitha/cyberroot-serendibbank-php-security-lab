<div class="auth-page">
    <section class="auth-intro"><div class="auth-intro-inner"><span class="eyebrow eyebrow-light">SerendibBank online</span><h1>Begin<br><em>here.</em></h1><p>Open an everyday account and keep your banking close, wherever life takes you.</p><div class="auth-intro-rule"></div><small>Banking for what matters next.</small></div></section>
    <section class="auth-form-wrap" aria-labelledby="register-title"><div class="auth-form-inner"><span class="eyebrow">Join SerendibBank</span><h2 id="register-title">Create your account</h2><p>Enter your details to set up your online banking access.</p>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="/register" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <label for="register-name">Full name</label><input id="register-name" name="name" type="text" autocomplete="name" minlength="2" maxlength="120" required value="<?= $escape($data['name'] ?? '') ?>">
            <label for="register-email">Email address</label><input id="register-email" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= $escape($data['email'] ?? '') ?>">
            <label for="register-password">Password</label><input id="register-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="256" required aria-describedby="register-password-help">
            <p class="field-help" id="register-password-help">Use at least 12 characters. You can use a passphrase.</p>
            <label for="register-confirmation">Confirm password</label><input id="register-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="256" required>
            <button class="button" type="submit">Create account <span aria-hidden="true">→</span></button>
        </form>
        <div class="form-aside"><strong>Already bank with us?</strong><p>Sign in with your existing online banking details.</p><a class="text-link" href="/login">Return to sign in <span aria-hidden="true">↗</span></a></div>
    </div></section>
</div>
