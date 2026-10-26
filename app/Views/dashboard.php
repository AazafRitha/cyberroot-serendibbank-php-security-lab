<section class="page-heading">
    <div>
        <div class="eyebrow">Personal banking</div>
        <h1>Account overview</h1>
        <p>Balances and recent activity in one place.</p>
    </div>
    <span class="status-pill"><?= count($data['accounts']) ?> accounts</span>
</section>

<section aria-labelledby="accounts-title">
    <h2 id="accounts-title" class="section-title">Accounts</h2>
    <div class="account-grid">
        <?php foreach ($data['accounts'] as $account): ?>
            <article class="account-card">
                <div class="account-top"><span><?= $escape($account['account_type']) ?></span><span>SerendibBank</span></div>
                <p class="account-number"><?= $escape($account['account_number']) ?></p>
                <p class="balance"><small>Available balance</small><strong><?= $escape($account['currency']) ?> <?= $escape($account['balance']) ?></strong></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section aria-labelledby="activity-title" class="activity-section">
    <h2 id="activity-title" class="section-title">Recent activity</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th scope="col">Date</th><th scope="col">Description</th><th scope="col">Amount</th></tr></thead>
            <tbody>
                <?php foreach ($data['transactions'] as $transaction): ?>
                    <tr>
                        <td><?= $escape(substr((string) $transaction['posted_at'], 0, 10)) ?></td>
                        <td><?= $escape($transaction['description']) ?></td>
                        <td class="amount"><?= $escape($transaction['currency']) ?> <?= $escape($transaction['amount']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
