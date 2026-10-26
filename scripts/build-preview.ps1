$project = Split-Path -Parent $PSScriptRoot
$layout = Get-Content -LiteralPath (Join-Path $project 'app\Views\layout.php') -Raw
$pages = @('home', 'personal', 'cards', 'offers', 'digital', 'locations', 'support')

foreach ($page in $pages) {
    $view = Get-Content -LiteralPath (Join-Path $project "app\Views\$page.php") -Raw
    if ($view.Contains('<?')) { throw "The $page view contains PHP and needs a preview renderer." }

    $html = $layout.Replace('<?= $content ?>', $view)
    $title = if ($page -eq 'home') { 'SerendibBank | Personal Banking' } else { "$($page.Substring(0,1).ToUpper())$($page.Substring(1)) | SerendibBank" }
    $html = [regex]::Replace($html, '<title>.*?</title>', "<title>$title</title>")
    $html = $html.Replace('<?= date(''Y'') ?>', (Get-Date).Year.ToString())
    $html = $html.Replace('href="/assets/', 'href="../public/assets/')
    $html = $html.Replace('src="/assets/', 'src="../public/assets/')

    foreach ($name in @('personal', 'cards', 'offers', 'digital', 'locations', 'support')) {
        $html = $html.Replace("href=`"/$name`"", "href=`"$name.html`"")
    }
    $html = $html.Replace('href="/dashboard"', 'href="accounts.html"')
    $html = $html.Replace('href="/login"', 'href="login.html"')
    $html = $html.Replace('href="/apply/account"', 'href="personal.html"')
    $html = $html.Replace('href="/apply/card"', 'href="cards.html"')
    $html = $html.Replace('href="/support/request"', 'href="support.html"')
    $html = $html.Replace('href="/transfer"', 'href="accounts.html"')
    $html = $html.Replace('href="/chat/transcript"', 'href="support.html"')
    $html = $html.Replace('href="/"', 'href="home.html"')

    Set-Content -LiteralPath (Join-Path $project "design\$page.html") -Value ($html.TrimEnd() + "`n") -Encoding utf8 -NoNewline
}

$accountsContent = @'
<div class="container">
<section class="page-heading"><div><span class="eyebrow">Personal banking</span><h1>Account overview</h1><p>Balances and recent activity in one place.</p></div><span class="status-pill">2 accounts</span></section>
<section aria-labelledby="accounts-title"><h2 id="accounts-title" class="section-title">Accounts</h2><div class="account-grid"><article class="account-card"><div class="account-top"><span>Everyday account</span><span>SerendibBank</span></div><p class="account-number">241000128453</p><p class="balance"><small>Available balance</small><strong>LKR 184,250.75</strong></p></article><article class="account-card"><div class="account-top"><span>Savings account</span><span>SerendibBank</span></div><p class="account-number">241000128454</p><p class="balance"><small>Available balance</small><strong>LKR 520,000.00</strong></p></article></div></section>
<section aria-labelledby="activity-title" class="activity-section"><h2 id="activity-title" class="section-title">Recent activity</h2><div class="table-wrap"><table><thead><tr><th scope="col">Date</th><th scope="col">Description</th><th scope="col">Amount</th></tr></thead><tbody><tr><td>2026-10-22</td><td>Salary payment</td><td class="amount">LKR 125,000.00</td></tr><tr><td>2026-10-20</td><td>Grocery purchase</td><td class="amount">LKR -6,850.50</td></tr><tr><td>2026-10-18</td><td>Electricity payment</td><td class="amount">LKR -4,300.00</td></tr></tbody></table></div></section>
</div>
'@
$accounts = $layout.Replace('<?= $content ?>', $accountsContent)
$accounts = [regex]::Replace($accounts, '<title>.*?</title>', '<title>Accounts | SerendibBank</title>')
$accounts = $accounts.Replace('<?= date(''Y'') ?>', (Get-Date).Year.ToString())
$accounts = $accounts.Replace('href="/assets/', 'href="../public/assets/')
$accounts = $accounts.Replace('src="/assets/', 'src="../public/assets/')
foreach ($name in @('personal', 'cards', 'offers', 'digital', 'locations', 'support')) {
    $accounts = $accounts.Replace("href=`"/$name`"", "href=`"$name.html`"")
}
$accounts = $accounts.Replace('href="/dashboard"', 'href="accounts.html"').Replace('href="/"', 'href="home.html"')
$accounts = $accounts.Replace('href="/login"', 'href="login.html"')
$accounts = $accounts.Replace('href="/chat/transcript"', 'href="support.html"')
Set-Content -LiteralPath (Join-Path $project 'design\accounts.html') -Value ($accounts.TrimEnd() + "`n") -Encoding utf8 -NoNewline

$loginContent = @'
<div class="auth-page"><section class="auth-intro"><div class="auth-intro-inner"><span class="eyebrow eyebrow-light">SerendibBank online</span><h1>Welcome<br><em>back.</em></h1><p>Your accounts, activity and next steps in one secure place.</p></div></section><section class="auth-form-wrap"><div class="auth-form-inner"><span class="eyebrow">Online banking</span><h2>Sign in to your account</h2><p>Enter your email address and password to continue.</p><form class="bank-form"><label for="login-email">Email address</label><input id="login-email" type="email" autocomplete="username"><label for="login-password">Password</label><input id="login-password" type="password" autocomplete="current-password"><button class="button" type="button">Sign in <span aria-hidden="true">→</span></button></form><div class="form-aside"><strong>Need access or help signing in?</strong><p>Contact your branch or service representative through a verified channel.</p><a class="text-link" href="support.html">Visit help centre ↗</a></div></div></section></div>
'@
$login = $layout.Replace('<?= $content ?>', $loginContent)
$login = [regex]::Replace($login, '<title>.*?</title>', '<title>Sign in | SerendibBank</title>')
$login = $login.Replace('<?= date(''Y'') ?>', (Get-Date).Year.ToString())
$login = $login.Replace('href="/assets/', 'href="../public/assets/').Replace('src="/assets/', 'src="../public/assets/')
foreach ($name in @('personal', 'cards', 'offers', 'digital', 'locations', 'support')) { $login = $login.Replace("href=`"/$name`"", "href=`"$name.html`"") }
$login = $login.Replace('href="/login"', 'href="login.html"').Replace('href="/"', 'href="home.html"')
$login = $login.Replace('href="/chat/transcript"', 'href="support.html"')
Set-Content -LiteralPath (Join-Path $project 'design\login.html') -Value ($login.TrimEnd() + "`n") -Encoding utf8 -NoNewline

Write-Output 'Updated static previews in design/.'
