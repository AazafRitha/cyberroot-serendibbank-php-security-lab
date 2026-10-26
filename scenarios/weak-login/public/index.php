<?php
declare(strict_types=1);
$users = ['n.perera@example.test' => 'Lotus2026!'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    // Deliberately distinct errors and no attempt limit. Fixture credentials only.
    $message = !array_key_exists($email, $users) ? 'No account exists for this email.' : ($users[$email] !== $password ? 'Incorrect password.' : 'Welcome, N. Perera.');
}
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Online banking | SerendibBank</title><style>body{margin:0;background:#faf6ee;color:#371629;font:16px Arial}header{background:#371629;color:#e4c27d;padding:22px 5vw;font:28px Georgia}main{max-width:520px;margin:4rem auto;background:white;padding:2rem;border-radius:16px}input{display:block;width:100%;padding:10px;margin:8px 0;box-sizing:border-box}button{background:#792b49;color:white;padding:12px 20px;border:0;border-radius:6px}</style></head><body><header>SerendibBank</header><main><h1>Online banking</h1><?php if ($message): ?><p role="status"><?= $escape($message) ?></p><?php endif; ?><form method="post"><label>Email<input name="email" type="email" required></label><label>Password<input name="password" type="password" required></label><button>Sign in</button></form></main></body></html>
