<div class="container portal-shell">
    <div class="portal-topline"><a href="<?= $data['kind'] === 'card' ? '/cards' : '/personal' ?>">← Back to <?= $data['kind'] === 'card' ? 'cards' : 'personal banking' ?></a><span>Applications</span></div>
    <section class="portal-header"><div><span class="eyebrow"><?= $data['kind'] === 'card' ? 'Cards' : 'Personal banking' ?></span><h1><?= $data['kind'] === 'card' ? 'Apply for a card.' : 'Apply for an account.' ?></h1><p>Tell us what you are looking for. Our team will review your request.</p></div></section>
    <div class="portal-layout"><section class="form-panel"><h2>Application details</h2>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="<?= $data['kind'] === 'card' ? '/apply/card' : '/apply/account' ?>" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <label for="application-product">Choose a product</label><select id="application-product" name="product" required <?= $data['kind'] === 'card' ? 'data-card-product' : '' ?>><option value="">Select a product</option><?php foreach ($data['products'] as $product): ?><option value="<?= $escape($product) ?>"><?= $escape($product) ?></option><?php endforeach; ?></select>
            <?php if ($data['kind'] === 'card'): ?>
                <div class="conditional-fields" data-debit-fields><span class="field-set-label">For a debit card</span><label for="linked-account">Account to link</label><select id="linked-account" name="linked_account"><option value="">Select an account</option><?php foreach ($data['accounts'] as $account): ?><option value="<?= $escape($account['id']) ?>"><?= $escape($account['account_type']) ?> ·•••<?= $escape(substr((string) $account['account_number'], -4)) ?></option><?php endforeach; ?></select></div>
                <div class="conditional-fields" data-credit-fields><span class="field-set-label">For a credit card</span><label for="employment">Employment status</label><select id="employment" name="employment_status"><option value="">Select a status</option><?php foreach ($data['employmentOptions'] as $status): ?><option value="<?= $escape($status) ?>"><?= $escape(ucfirst(str_replace('-', ' ', $status))) ?></option><?php endforeach; ?></select><label for="income">Monthly income (LKR)</label><input id="income" name="monthly_income" type="text" inputmode="decimal" pattern="[0-9]+(\.[0-9]{1,2})?" maxlength="12" placeholder="0.00"></div>
            <?php endif; ?>
            <label for="application-notes">Anything we should know? <span class="label-optional">Optional</span></label><textarea id="application-notes" name="notes" rows="4" maxlength="500" placeholder="Add information that will help us review your application."></textarea>
            <button class="button" type="submit">Submit application <span aria-hidden="true">→</span></button>
        </form>
    </section><aside class="portal-aside"><span class="aside-icon" aria-hidden="true">◇</span><h2>What happens next?</h2><p>Your request will appear in your banking dashboard with its current status. A member of the team can review it there.</p><p>Submitting this form does not open an account or issue a card immediately.</p></aside></div>
</div>
