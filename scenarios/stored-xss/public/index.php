<?php
declare(strict_types=1);
session_start();
$_SESSION['messages'] ??= [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim((string) ($_POST['message'] ?? ''));
    if ($message !== '') {
        $_SESSION['messages'][] = substr($message, 0, 500);
    }
    header('Location: /');
    exit;
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Secure message centre | SerendibBank</title><style>body{margin:0;background:#faf6ee;color:#371629;font:16px Arial}header{background:#371629;color:#e4c27d;padding:22px 5vw;font:28px Georgia}main{max-width:720px;margin:4rem auto;background:white;padding:2rem;border-radius:16px}textarea{width:100%;min-height:100px}button{background:#792b49;color:white;padding:12px 20px;border:0;border-radius:6px;margin-top:12px}article{padding:1rem;border-bottom:1px solid #ddd}</style></head><body><header>SerendibBank</header><main><h1>Secure messages</h1><form method="post"><label for="message">Your message</label><textarea id="message" name="message" maxlength="500" required></textarea><button>Send message</button></form><h2>Conversation</h2><?php foreach ($_SESSION['messages'] as $message): ?><article><?= $message /* Deliberately unescaped stored content. */ ?></article><?php endforeach; ?></main></body></html>
