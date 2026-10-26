<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $escape($data['title'] ?? 'SerendibBank') ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="/" aria-label="SerendibBank home">
                <span class="brand-mark">SB</span>
                <span><strong>SerendibBank</strong><small>Personal banking</small></span>
            </a>
            <nav aria-label="Main navigation">
                <a href="/">Home</a>
                <a href="/dashboard">Accounts</a>
            </nav>
        </div>
    </header>

    <main id="main" class="container"><?= $content ?></main>

    <footer class="site-footer container">
        <span>&copy; <?= date('Y') ?> SerendibBank</span>
        <span>Banking for what matters next.</span>
    </footer>
</body>
</html>
