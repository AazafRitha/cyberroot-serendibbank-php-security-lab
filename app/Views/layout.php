<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3b1725">
    <title><?= $escape($data['title'] ?? 'SerendibBank') ?></title>
    <script>try { document.documentElement.dataset.theme = localStorage.getItem('serendib-theme') === 'dark' ? 'dark' : 'light'; } catch { document.documentElement.dataset.theme = 'light'; }</script>
    <link rel="stylesheet" href="/assets/style.css?v=7">
    <link rel="stylesheet" href="/assets/portal.css?v=2">
    <link rel="stylesheet" href="/assets/chat.css?v=3">
    <link rel="stylesheet" href="/assets/theme.css?v=3">
    <link rel="stylesheet" href="/assets/home-preview.css?v=1">
    <link rel="icon" type="image/svg+xml" href="/assets/logo-mark.svg?v=2">
    <script src="/assets/site.js?v=5" defer></script>
    <script src="/assets/chat.js" defer></script>
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
                <img class="brand-mark" src="/assets/logo-mark.svg?v=2" alt="" width="43" height="43">
                <span class="brand-name"><strong>Serendib<span>Bank</span></strong><small>Here for every tomorrow</small></span>
            </a>
            <nav class="primary-nav" id="primary-navigation" aria-label="Main navigation">
                <span class="mobile-nav-heading">Explore SerendibBank</span>
                <?php
                $currentPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
                $currentPath = $currentPath === '/' ? '/' : rtrim($currentPath, '/');
                $navLinks = [
                    '/' => 'Home',
                    '/personal' => 'Personal',
                    '/cards' => 'Cards',
                    '/offers' => 'Offers',
                    '/digital' => 'Digital banking',
                    '/locations' => 'Locations',
                ];
                $activeNav = match (true) {
                    $currentPath === '/' => '/',
                    str_starts_with($currentPath, '/personal'),
                    str_starts_with($currentPath, '/savings/'),
                    $currentPath === '/apply/account' => '/personal',
                    str_starts_with($currentPath, '/cards'),
                    $currentPath === '/apply/card' => '/cards',
                    str_starts_with($currentPath, '/offers') => '/offers',
                    str_starts_with($currentPath, '/digital') => '/digital',
                    str_starts_with($currentPath, '/locations') => '/locations',
                    default => null,
                };
                foreach ($navLinks as $path => $label):
                ?>
                <a href="<?= $path ?>"<?= $activeNav === $path ? ' aria-current="page"' : '' ?>><?= $label ?></a>
                <?php endforeach; ?>
                <a class="mobile-nav-login" href="<?= $data['currentUser'] !== null ? ($data['currentUser']['role'] === 'staff' ? '/staff' : '/dashboard') : '/login' ?>"><?= $data['currentUser'] !== null ? ($data['currentUser']['role'] === 'staff' ? 'Service workspace' : 'My banking') : 'Online banking' ?> <span aria-hidden="true">↗</span></a>
            </nav>
            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Switch to dark mode" aria-pressed="false"><span class="theme-toggle-track" aria-hidden="true"><span class="theme-toggle-thumb"><span class="icon icon-sun"></span><span class="icon icon-moon"></span></span></span><span class="sr-only" data-theme-label>Dark mode</span></button>
            <button class="menu-toggle" type="button" data-menu-toggle aria-controls="primary-navigation" aria-expanded="false" aria-label="Open menu"><span class="menu-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span></button>
            <?php if ($data['currentUser'] !== null): ?><a class="header-login" href="<?= $data['currentUser']['role'] === 'staff' ? '/staff' : '/dashboard' ?>"><?= $data['currentUser']['role'] === 'staff' ? 'Service workspace' : 'My banking' ?> <span aria-hidden="true">↗</span></a><?php else: ?><a class="header-login" href="/login">Online banking <span aria-hidden="true">↗</span></a><?php endif; ?>
        </div>
    </header>

    <main id="main"><?= $content ?></main>

    <footer class="site-footer">
        <div class="container footer-top">
            <div class="footer-intro"><a class="brand brand-light" href="/"><img class="brand-mark" src="/assets/logo-mark.svg?v=2" alt="" width="43" height="43"><span class="brand-name"><strong>Serendib<span>Bank</span></strong><small>Here for every tomorrow</small></span></a><p>Thoughtful banking for the moments that matter, wherever life takes you.</p></div>
            <div><h2>Explore</h2><a href="/personal">Personal banking</a><a href="/cards">Cards</a><a href="/offers">Card offers</a><a href="/digital">Digital banking</a></div>
            <div><h2>Useful links</h2><a href="/locations">Branches &amp; ATMs</a><a href="/documents">Product information</a><a href="/support">Help &amp; support</a><a href="/login">Online banking</a></div>
            <div><h2>Need help?</h2><p>Find answers to common questions or visit a branch near you.</p><a class="footer-help" href="/support">Visit help centre <span aria-hidden="true">↗</span></a></div>
        </div>
        <div class="container footer-bottom"><span>&copy; <?= date('Y') ?> SerendibBank. All rights reserved.</span><span>Banking for what matters next.</span></div>
    </footer>
    <aside class="chat-widget" aria-label="Digital assistant">
        <section class="chat-panel" id="chat-panel" aria-label="Chat with SerendibBank" hidden>
            <div class="chat-panel-header"><img class="chat-avatar" src="/assets/logo-mark.svg?v=2" alt="" width="40" height="40"><div><strong>SerendibBank assistant</strong><small>Here to help you find your way</small></div><button type="button" class="chat-close" data-chat-close aria-label="Close chat">×</button></div>
            <div class="chat-messages" data-chat-messages role="log" aria-live="polite" aria-relevant="additions"><div class="chat-message chat-message-assistant">Hello. Ask me about accounts, cards, transfers, branches or support.</div></div>
            <div class="chat-suggestions" data-chat-suggestions><button type="button" data-chat-suggestion="How do I apply for a card?">Apply for a card</button><button type="button" data-chat-suggestion="Where is a branch?">Find a branch</button><button type="button" data-chat-suggestion="How do I transfer money?">Transfer money</button></div>
            <form class="chat-form" data-chat-form><label class="sr-only" for="chat-message">Your message</label><input id="chat-message" name="message" maxlength="500" autocomplete="off" placeholder="Ask a question" required><button type="submit" aria-label="Send message">→</button></form>
            <p class="chat-footnote">Never share passwords, PINs or one-time codes here. <a href="/chat/transcript">View conversation</a></p>
        </section>
        <button class="chat-launcher" type="button" data-chat-toggle aria-controls="chat-panel" aria-expanded="false" aria-label="Chat with us"><img src="/assets/logo-mark.svg?v=2" alt="" width="30" height="30"><strong>Chat with us</strong></button>
    </aside>
</body>
</html>
