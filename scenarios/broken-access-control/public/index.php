<?php

declare(strict_types=1);

// Deliberately missing ownership check: the signed-in fixture is customer 1.
$accounts = [
    1 => ['owner' => 'N. Perera', 'number' => '241000128453', 'balance' => 'LKR 184,250.75'],
    2 => ['owner' => 'M. Fernando', 'number' => '241000128455', 'balance' => 'LKR 92,000.00'],
];
$requested = filter_var($_GET['account'] ?? 1, FILTER_VALIDATE_INT);
$account = $accounts[$requested] ?? null;
if ($account === null) {
    http_response_code(404);
}
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Account statement | SerendibBank</title><style>body{font:16px Arial,sans-serif;background:#faf6ee;color:#3b1725;margin:0}header{background:#3b1725;color:#e0be78;padding:22px 5vw;font:26px Georgia,serif}main{max-width:700px;margin:60px auto;background:#fff;padding:35px;box-shadow:0 10px 30px #3b17251a}a{color:#7d3048}dl{display:grid;grid-template-columns:150px 1fr;gap:15px}dt{font-weight:bold}</style></head><body><header>SerendibBank</header><main><p>Signed in as N. Perera</p><h1>Account statement</h1><?php if ($account): ?><dl><dt>Account holder</dt><dd><?= $escape($account['owner']) ?></dd><dt>Account number</dt><dd><?= $escape($account['number']) ?></dd><dt>Available balance</dt><dd><?= $escape($account['balance']) ?></dd></dl><?php else: ?><p>Account not found.</p><?php endif; ?><p><a href="/?account=1">Back to my statement</a></p></main></body></html>
