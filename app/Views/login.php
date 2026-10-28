<div class="auth-page">
    <section class="auth-intro"><div class="auth-intro-inner"><span class="eyebrow eyebrow-light">SerendibBank online</span><h1>Welcome<br><em>back.</em></h1><p>Your accounts, activity and next steps in one secure place.</p><div class="auth-intro-rule"></div><small>Banking for what matters next.</small></div></section>
    <section class="auth-form-wrap" aria-labelledby="login-title"><div class="auth-form-inner"><span class="eyebrow">Online banking</span><h2 id="login-title">Sign in to your account</h2><p>Enter your email address and password to continue.</p>
        <?php if (!empty($data['success'])): ?><div class="form-success" role="status"><?= $escape($data['success']) ?></div><?php endif; ?>
        <?php if (!empty($data['accountNumber'])): ?><p class="registration-account-number">Your account number: <strong><?= $escape($data['accountNumber']) ?></strong>. Keep it for your records.</p><?php endif; ?>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <?php if (!empty($data['retrySeconds'])): ?><p class="login-cooldown" data-login-cooldown="<?= $escape($data['retrySeconds']) ?>" role="status">Try again in <strong data-login-cooldown-time><?= $escape((int) ceil($data['retrySeconds'] / 60)) ?> minutes</strong>.</p><?php endif; ?>
        <form method="post" action="/login" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <label for="login-email">Email address</label><input id="login-email" name="email" type="email" autocomplete="username" maxlength="190" required value="<?= $escape($data['email'] ?? '') ?>">
            <label for="login-password">Password</label><input id="login-password" name="password" type="password" autocomplete="current-password" required>
            <button class="button" type="submit">Sign in <span aria-hidden="true">→</span></button>
        </form>
        <div class="form-aside"><strong>New to SerendibBank?</strong><p>Create an online banking profile and an everyday account.</p><a class="text-link" href="/register">Register now <span aria-hidden="true">↗</span></a></div>
    </div></section>
</div>
