<?php

declare(strict_types=1);

use CyberRoot\SerendibBank\Core\OtpOutbox;

require dirname(__DIR__) . '/app/bootstrap.php';

if (PHP_SAPI !== 'cli' || count($argv) !== 2 || !filter_var($argv[1], FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/read-otp.php email@example.test\n");
    exit(1);
}

$code = OtpOutbox::latestCode($argv[1]);
if ($code === null) {
    fwrite(STDERR, "No current verification code was found.\n");
    exit(1);
}
fwrite(STDOUT, $code . "\n");
