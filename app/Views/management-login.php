<div class="management-auth-shell">
    <section class="management-auth-story"><span class="management-eyebrow">SerendibBank operations</span><h1>Clarity for<br><em>every decision.</em></h1><p>One place to guide customer service, review requests, and keep the bank moving.</p><div class="management-story-line"></div><small>Authorized personnel access</small></section>
    <section class="management-auth-card" aria-labelledby="management-login-title"><span class="management-eyebrow">Management access</span><h2 id="management-login-title">Welcome back.</h2><p>Sign in with your staff email address and password.</p>
        <?php if (!empty($data['error'])): ?><div class="management-alert" role="alert"><?= $escape($data['error']) ?></div><?php endif; ?>
        <form method="post" action="/login" class="management-form"><input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>"><label for="management-email">Email address</label><input id="management-email" name="email" type="email" autocomplete="username" required maxlength="190"><label for="management-password">Password</label><input id="management-password" name="password" type="password" autocomplete="current-password" required><button class="management-button" type="submit">Continue securely <span aria-hidden="true">→</span></button></form>
        <p class="management-auth-note">Access is recorded under your individual account.</p>
    </section>
</div>
