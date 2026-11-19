<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}

if ($argc !== 1) {
    fwrite(STDERR, "User counts are defined in private/practice-users.csv. Run scripts/import-practice-users.php --validate before importing.\n");
    exit(1);
}

require __DIR__ . '/import-practice-users.php';
