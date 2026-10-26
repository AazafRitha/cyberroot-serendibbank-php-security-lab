<?php

declare(strict_types=1);

$query = $_GET['q'] ?? '';
$query = is_string($query) ? substr($query, 0, 120) : '';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$rows = [];
if ($query !== '') {
    $pdo = new PDO(
        'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
        getenv('DB_USER'), getenv('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Deliberately vulnerable: untrusted search text is concatenated into SQL.
    $sql = "SELECT city, service_note FROM branches WHERE city LIKE '%" . $query . "%' ORDER BY city";
    try {
        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $exception) {
        http_response_code(400);
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Branch finder | SerendibBank</title><style>body{font:16px Arial,sans-serif;background:#faf6ee;color:#3b1725;margin:0}header{background:#3b1725;color:#e0be78;padding:22px 5vw;font:26px Georgia,serif}main{max-width:700px;margin:60px auto;background:#fff;padding:35px}input,button{padding:12px;font:inherit}input{width:60%;border:1px solid #cbb}button{background:#7d3048;color:#fff;border:0}li{padding:14px;border-bottom:1px solid #ddd;list-style:none}ul{padding:0}</style></head><body><header>SerendibBank</header><main><h1>Find a branch</h1><form method="get"><label for="q">City</label><br><input id="q" name="q" value="<?= $escape($query) ?>"><button>Search</button></form><ul><?php foreach ($rows as $row): ?><li><strong><?= $escape($row['city']) ?></strong><br><?= $escape($row['service_note']) ?></li><?php endforeach; ?></ul></main></body></html>
