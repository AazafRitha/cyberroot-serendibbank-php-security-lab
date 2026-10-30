<section class="settings-hero">
    <div class="container settings-hero-inner"><div><span class="eyebrow eyebrow-light">Online banking</span><h1>Your account.<br><em>In your hands.</em></h1><p>Review where you are signed in and end sessions you no longer use.</p></div><img src="/assets/account-settings.webp" alt="Customer managing banking details on a laptop at home"></div>
</section>
<div class="container portal-shell settings-shell">
    <div class="portal-topline"><a href="/settings">← Back to settings</a><span>Sign-in &amp; security</span></div>
    <section class="portal-header"><div><span class="eyebrow">Account access</span><h1>Signed-in devices.</h1><p>These are the recent browsers and devices connected to your account.</p></div></section>
    <?php if ($data['flash']): ?><div class="form-success" role="status"><?= $escape($data['flash']) ?></div><?php endif; ?>
    <div class="session-page-head"><span><?= count($data['sessions']) ?> recent sessions</span><form method="post" action="/sessions/revoke"><input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>"><input type="hidden" name="action" value="others"><button class="button" type="submit">End other sessions</button></form></div>
    <div class="session-list">
        <?php foreach ($data['sessions'] as $session): ?>
            <?php $current = (int) $session['id'] === $data['currentSessionId']; $ended = $session['revoked_at'] !== null; ?>
            <article class="session-card">
                <div class="session-icon" aria-hidden="true">◈</div>
                <div class="session-details"><div class="session-title"><strong><?= $escape($session['user_agent']) ?></strong><?php if ($current): ?><span>Current device</span><?php endif; ?><?php if ($ended): ?><span class="session-ended">Ended</span><?php endif; ?></div><p>IP <?= $escape($session['ip_address']) ?> · Signed in <?= $escape($session['created_at']) ?> UTC</p><small>Last activity <?= $escape($session['last_seen_at']) ?> UTC</small></div>
                <?php if (!$ended): ?><form method="post" action="/sessions/revoke"><input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>"><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?= $escape($session['id']) ?>"><button type="submit"><?= $current ? 'Sign out here' : 'End session' ?></button></form><?php endif; ?>
            </article>
        <?php endforeach; ?>
        <?php if ($data['sessions'] === []): ?><p class="session-empty">No sessions to show.</p><?php endif; ?>
    </div>
</div>
