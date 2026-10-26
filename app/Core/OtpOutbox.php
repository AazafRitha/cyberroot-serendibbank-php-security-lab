<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

use RuntimeException;

final class OtpOutbox
{
    private static function path(string $email): string
    {
        return dirname(__DIR__, 2) . '/var/otp/' . hash('sha256', strtolower(trim($email))) . '.json';
    }

    public static function deliver(string $email, string $code): void
    {
        $directory = dirname(self::path($email));
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('Verification delivery is unavailable.');
        }
        $record = json_encode(['email' => $email, 'code' => $code, 'issued_at' => time()], JSON_THROW_ON_ERROR);
        if (file_put_contents(self::path($email), $record, LOCK_EX) === false) {
            throw new RuntimeException('Verification delivery is unavailable.');
        }
        @chmod(self::path($email), 0600);
    }

    public static function latestCode(string $email): ?string
    {
        $record = @file_get_contents(self::path($email));
        if ($record === false) {
            return null;
        }
        $data = json_decode($record, true);
        if (!is_array($data) || !is_string($data['code'] ?? null) || (int) ($data['issued_at'] ?? 0) < time() - 300) {
            return null;
        }
        return $data['code'];
    }

    public static function clear(string $email): void
    {
        $path = self::path($email);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
