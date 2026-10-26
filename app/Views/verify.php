<div class="auth-page">
    <section class="auth-intro"><div class="auth-intro-inner"><span class="eyebrow eyebrow-light">SerendibBank online</span><h1>One more<br><em>step.</em></h1><p>Keep your banking close and your account protected.</p><div class="auth-intro-rule"></div><small>Banking for what matters next.</small></div></section>
    <section class="auth-form-wrap" aria-labelledby="verify-title"><div class="auth-form-inner"><span class="eyebrow">Online banking</span><h2 id="verify-title">Verify your sign in</h2><p>Enter the six-digit code for <?= $escape($data['maskedEmail']) ?>. The code expires in five minutes.</p>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="/verify" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <label for="verification-code">Verification code</label><input id="verification-code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus>
            <button class="button" type="submit">Continue to banking <span aria-hidden="true">→</span></button>
        </form>
        <div class="form-aside"><strong>Need a new code?</strong><p>Return to sign in to request another verification code.</p><a class="text-link" href="/login">Return to sign in <span aria-hidden="true">↗</span></a></div>
    </div></section>
</div>
