<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\Database;

require dirname(__DIR__) . '/app/bootstrap.php';

$pdo = Database::connect();
foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [] as $migration) {
    $sql = file_get_contents($migration);
    if ($sql === false) {
        throw new RuntimeException('Cannot read migration: ' . basename($migration));
    }
    $pdo->exec($sql);
    fwrite(STDOUT, 'Applied ' . basename($migration) . PHP_EOL);
}
