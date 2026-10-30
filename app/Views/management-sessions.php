<div class="management-shell">
    <nav class="management-nav" aria-label="Workspace sections"><span class="management-nav-title">Workspace</span><a href="/">← Overview</a><a href="/sessions" aria-current="page">My sessions</a></nav>
    <div class="management-main">
        <section class="management-hero"><div><span class="management-eyebrow">Account access</span><h1>Signed-in<br><em>devices.</em></h1><p>Review your recent management sign-ins and end sessions you no longer use.</p></div><div class="management-hero-mark" aria-hidden="true"><img src="/assets/logo-mark.svg" alt=""></div></section>
        <?php if ($data['flash']): ?><div class="management-success" role="status"><?= $escape($data['flash']) ?></div><?php endif; ?>
        <section class="management-section"><div class="management-section-head"><div><span class="management-eyebrow">Your access</span><h2>Recent sessions</h2><p>Signed-in browsers and devices associated with your account.</p></div><form method="post" action="/sessions/revoke"><input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>"><input type="hidden" name="action" value="others"><button class="management-button" type="submit">End other sessions</button></form></div>
            <div class="management-session-list">
                <?php foreach ($data['sessions'] as $session): ?>
                    <?php $current = (int) $session['id'] === $data['currentSessionId']; $ended = $session['revoked_at'] !== null; ?>
                    <article class="management-session-card"><div><strong><?= $escape($session['user_agent']) ?></strong><?php if ($current): ?><span class="management-state is-active">Current device</span><?php endif; ?><?php if ($ended): ?><span class="management-state is-suspended">Ended</span><?php endif; ?><p>IP <?= $escape($session['ip_address']) ?> · Signed in <?= $escape($session['created_at']) ?> UTC</p><small>Last activity <?= $escape($session['last_seen_at']) ?> UTC</small></div><?php if (!$ended): ?><form method="post" action="/sessions/revoke"><input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>"><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?= $escape($session['id']) ?>"><button type="submit"><?= $current ? 'Sign out here' : 'End session' ?></button></form><?php endif; ?></article>
                <?php endforeach; ?>
                <?php if ($data['sessions'] === []): ?><p class="management-empty">No sessions to show.</p><?php endif; ?>
            </div>
        </section>
    </div>
</div>
