<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = Database::connect();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (filename VARCHAR(190) PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)');
foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [] as $migration) {
    $filename = basename($migration);
    $applied = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE filename = ?');
    $applied->execute([$filename]);
    if ($applied->fetchColumn() !== false) {
        continue;
    }
    $sql = file_get_contents($migration);
    if ($sql === false) {
        throw new RuntimeException('Cannot read migration: ' . $filename);
    }
    $pdo->exec($sql);
    $record = $pdo->prepare('INSERT INTO schema_migrations (filename) VALUES (?)');
    $record->execute([$filename]);
    fwrite(STDOUT, 'Applied ' . $filename . PHP_EOL);
}
