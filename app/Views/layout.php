<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3b1725">
    <title><?= $escape($data['title'] ?? 'SerendibBank') ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <link rel="stylesheet" href="/assets/portal.css">
    <link rel="icon" type="image/svg+xml" href="/assets/logo-mark.svg">
    <script src="/assets/site.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="utility-bar">
        <div class="container utility-inner">
            <span>Banking shaped around your life</span>
            <div><a href="/locations">Branches &amp; ATMs</a><a href="/support">Help centre</a><span>English <span aria-hidden="true">⌄</span></span></div>
        </div>
    </div>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="/" aria-label="SerendibBank home">
                <img class="brand-mark" src="/assets/logo-mark.svg" alt="" width="43" height="43">
                <span class="brand-name"><strong>Serendib<span>Bank</span></strong><small>Here for every tomorrow</small></span>
            </a>
            <nav class="primary-nav" aria-label="Main navigation">
                <a href="/personal">Personal</a>
                <a href="/cards">Cards</a>
                <a href="/offers">Offers</a>
                <a href="/digital">Digital banking</a>
                <a href="/locations">Locations</a>
            </nav>
            <a class="header-login" href="/login">Online banking <span aria-hidden="true">↗</span></a>
        </div>
    </header>

    <main id="main"><?= $content ?></main>

    <footer class="site-footer">
        <div class="container footer-top">
            <div class="footer-intro"><a class="brand brand-light" href="/"><img class="brand-mark" src="/assets/logo-mark.svg" alt="" width="43" height="43"><span class="brand-name"><strong>Serendib<span>Bank</span></strong><small>Here for every tomorrow</small></span></a><p>Thoughtful banking for the moments that matter, wherever life takes you.</p></div>
            <div><h2>Explore</h2><a href="/personal">Personal banking</a><a href="/cards">Cards</a><a href="/offers">Card offers</a><a href="/digital">Digital banking</a></div>
            <div><h2>Useful links</h2><a href="/locations">Branches &amp; ATMs</a><a href="/support">Help &amp; support</a><a href="/login">Online banking</a></div>
            <div><h2>Need help?</h2><p>Find answers to common questions or visit a branch near you.</p><a class="footer-help" href="/support">Visit help centre <span aria-hidden="true">↗</span></a></div>
        </div>
        <div class="container footer-bottom"><span>&copy; <?= date('Y') ?> SerendibBank. All rights reserved.</span><span>Banking for what matters next.</span></div>
    </footer>
</body>
</html>
