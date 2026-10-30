<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#32151f">
    <title><?= $escape($data['title'] ?? 'SerendibBank Management') ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/logo-mark.svg">
    <link rel="stylesheet" href="/assets/management.css?v=3">
</head>
<body class="management-body">
    <a class="management-skip" href="#content">Skip to content</a>
    <header class="management-topbar">
        <a class="management-brand" href="/"><img src="/assets/logo-mark.svg" alt="" width="42" height="42"><span><strong>SerendibBank</strong><small>Management workspace</small></span></a>
        <?php if ($data['currentUser'] !== null && in_array($data['currentUser']['role'], ['staff', 'admin', 'super_admin'], true)): ?>
            <div class="management-session"><span><?= $escape($data['currentUser']['name']) ?> <b><?= $escape(ucfirst($data['currentUser']['role'])) ?></b></span><form method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= $escape($data['csrfToken']) ?>"><button type="submit">Sign out <span aria-hidden="true">↗</span></button></form></div>
        <?php endif; ?>
    </header>
    <main id="content"><?= $content ?></main>
    <footer class="management-footer"><span>SerendibBank · Internal operations</span><span>&copy; <?= date('Y') ?> SerendibBank</span></footer>
</body>
</html>
