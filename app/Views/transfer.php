<div class="container portal-shell">
    <div class="portal-topline"><a href="/dashboard">← Back to dashboard</a><span>Online banking</span></div>
    <section class="portal-header"><div><span class="eyebrow">Payments &amp; transfers</span><h1>Transfer between your accounts.</h1><p>Move money between your own SerendibBank accounts.</p></div></section>
    <div class="portal-layout"><section class="form-panel" aria-labelledby="transfer-title"><h2 id="transfer-title">Transfer details</h2>
        <?php if (!empty($data['error'])): ?><div class="form-error" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="/transfer" class="bank-form">
            <input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>">
            <input type="hidden" name="request_token" value="<?= $escape($data['requestToken']) ?>">
            <label for="transfer-from">From account</label><select id="transfer-from" name="from_account" required><option value="">Select an account</option><?php foreach ($data['accounts'] as $account): ?><option value="<?= $escape($account['id']) ?>"><?= $escape($account['account_type']) ?> ·•••<?= $escape(substr((string) $account['account_number'], -4)) ?> · <?= $escape($account['currency']) ?> <?= $escape($account['balance']) ?></option><?php endforeach; ?></select>
            <label for="transfer-to">To account</label><select id="transfer-to" name="to_account" required><option value="">Select an account</option><?php foreach ($data['accounts'] as $account): ?><option value="<?= $escape($account['id']) ?>"><?= $escape($account['account_type']) ?> ·•••<?= $escape(substr((string) $account['account_number'], -4)) ?></option><?php endforeach; ?></select>
            <label for="transfer-amount">Amount (LKR)</label><input id="transfer-amount" name="amount" inputmode="decimal" type="text" pattern="[0-9]+(\.[0-9]{1,2})?" placeholder="0.00" maxlength="10" required>
            <p class="field-help">Transfers are available between accounts in the same currency. Maximum LKR 1,000,000.00 per transfer.</p>
            <button class="button" type="submit">Complete transfer <span aria-hidden="true">→</span></button>
        </form>
    </section><aside class="portal-aside"><span class="aside-icon" aria-hidden="true">✦</span><h2>Before you continue</h2><p>Check both account numbers and the amount carefully. Your balances and activity will update after the transfer completes.</p><p>If something does not look right, stop and contact support.</p><a class="text-link" href="/support/request">Contact support <span aria-hidden="true">↗</span></a></aside></div>
</div>
