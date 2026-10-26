<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $escape($data['title'] ?? 'CyberRoot SerendibBank') ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="/" aria-label="CyberRoot SerendibBank home">
                <span class="brand-mark">CR</span>
                <span><strong>SerendibBank</strong><small>by CyberRoot</small></span>
            </a>
            <nav aria-label="Main navigation">
                <a href="/">Home</a>
                <a href="/dashboard">Demo dashboard</a>
            </nav>
        </div>
    </header>

    <main id="main" class="container"><?= $content ?></main>

    <footer class="site-footer container">
        <span>CyberRoot security lab</span>
        <span>Fictional accounts and local training data only</span>
    </footer>
</body>
</html>
