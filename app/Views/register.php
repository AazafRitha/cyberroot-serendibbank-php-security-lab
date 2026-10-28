<div class="auth-page">
    <section class="auth-intro"><div class="auth-intro-inner"><span class="eyebrow eyebrow-light">SerendibBank online</span><h1>Begin<br><em>here.</em></h1><p>Open an everyday account and keep your banking close, wherever life takes you.</p><div class="auth-intro-rule"></div><small>Banking for what matters next.</small></div></section>
    <section class="auth-form-wrap" aria-labelledby="register-title"><div class="auth-form-inner"><span class="eyebrow">Join SerendibBank</span><h2 id="register-title">Create your account</h2><p>Enter your details to set up your online banking access.</p>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="/register" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <span class="field-set-label">Your details</span>
            <label for="register-name">Full name</label><input id="register-name" name="name" type="text" autocomplete="name" minlength="2" maxlength="120" required value="<?= $escape($data['name'] ?? '') ?>">
            <label for="register-email">Email address</label><input id="register-email" name="email" type="email" autocomplete="email" maxlength="190" required value="<?= $escape($data['email'] ?? '') ?>">
            <div class="registration-row">
                <div class="registration-field"><label for="register-identity-type">Identity document</label><select id="register-identity-type" name="identity_type" required><option value="">Choose a document</option><option value="nic" <?= ($data['identityType'] ?? '') === 'nic' ? 'selected' : '' ?>>National Identity Card</option><option value="passport" <?= ($data['identityType'] ?? '') === 'passport' ? 'selected' : '' ?>>Passport</option></select></div>
                <div class="registration-field"><label for="register-identity-number">Document number</label><input id="register-identity-number" name="identity_number" type="text" autocomplete="off" maxlength="20" required aria-describedby="register-identity-help"></div>
            </div>
            <p class="field-help" id="register-identity-help">Use your 12-digit NIC, older NIC number, or passport number. Your document number will not be displayed again.</p>
            <span class="field-set-label">Your address</span>
            <label for="register-address-line1">Street address</label><input id="register-address-line1" name="address_line1" type="text" autocomplete="address-line1" minlength="5" maxlength="160" required value="<?= $escape($data['addressLine1'] ?? '') ?>">
            <label for="register-address-line2">Apartment or building <span class="label-optional">optional</span></label><input id="register-address-line2" name="address_line2" type="text" autocomplete="address-line2" maxlength="160" value="<?= $escape($data['addressLine2'] ?? '') ?>">
            <div class="registration-row registration-city-row">
                <div class="registration-field"><label for="register-city">City or town</label><input id="register-city" name="city" type="text" autocomplete="address-level2" minlength="2" maxlength="80" required value="<?= $escape($data['city'] ?? '') ?>"></div>
                <div class="registration-field"><label for="register-postal-code">Postal code</label><input id="register-postal-code" name="postal_code" type="text" autocomplete="postal-code" minlength="3" maxlength="12" required value="<?= $escape($data['postalCode'] ?? '') ?>"></div>
            </div>
            <span class="field-set-label">Sign in details</span>
            <label for="register-password">Password</label><input id="register-password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="256" required aria-describedby="register-password-help">
            <p class="field-help" id="register-password-help">Use at least 12 characters. You can use a passphrase.</p>
            <label for="register-confirmation">Confirm password</label><input id="register-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="256" required>
            <p class="field-help">Your unique account number will be assigned after registration.</p>
            <button class="button" type="submit">Create account <span aria-hidden="true">→</span></button>
        </form>
        <div class="form-aside"><strong>Already bank with us?</strong><p>Sign in with your existing online banking details.</p><a class="text-link" href="/login">Return to sign in <span aria-hidden="true">↗</span></a></div>
    </div></section>
</div>
