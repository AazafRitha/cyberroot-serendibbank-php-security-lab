<?php
declare(strict_types=1);
session_start();
$_SESSION['balance'] ??= 1000;
$_SESSION['transfers'] ??= [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_INT);
    if ($amount && $amount > 0 && $amount <= $_SESSION['balance']) {
        // Deliberately no anti-CSRF token check.
        $_SESSION['balance'] -= $amount;
        $_SESSION['transfers'][] = ['amount' => $amount, 'payee' => substr((string) ($_POST['payee'] ?? ''), 0, 60)];
    }
    header('Location: /');
    exit;
}
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pay a saved payee | SerendibBank</title><style>body{margin:0;background:#faf6ee;color:#371629;font:16px Arial}header{background:#371629;color:#e4c27d;padding:22px 5vw;font:28px Georgia}main{max-width:720px;margin:4rem auto;background:white;padding:2rem;border-radius:16px}input{padding:10px;margin:8px}button{background:#792b49;color:white;padding:12px 20px;border:0;border-radius:6px}</style></head><body><header>SerendibBank</header><main><h1>Pay a saved payee</h1><p>Available balance: LKR <?= $escape($_SESSION['balance']) ?></p><form method="post"><label>Payee <input name="payee" required></label><label>Amount <input name="amount" type="number" min="1" max="1000" required></label><button>Transfer</button></form><h2>Recent transfers</h2><?php foreach ($_SESSION['transfers'] as $transfer): ?><p><?= $escape($transfer['payee']) ?> — LKR <?= $escape($transfer['amount']) ?></p><?php endforeach; ?></main></body></html>
