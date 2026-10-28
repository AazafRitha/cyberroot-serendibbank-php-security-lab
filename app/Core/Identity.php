<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

use RuntimeException;

final class Identity
{
    public static function normalize(string $type, string $number): ?string
    {
        if (strlen($number) > 32) {
            return null;
        }
        $number = strtoupper(preg_replace('/[\s-]+/', '', $number) ?? '');
        if ($type === 'nic') {
            if (preg_match('/^[0-9]{12}$/D', $number)) {
                return $number;
            }
            if (preg_match('/^[0-9]{9}[VX]$/D', $number)) {
                $old = substr($number, 0, 9);
                return '19' . substr($old, 0, 5) . '0' . substr($old, 5, 4);
            }
            return null;
        }
        if ($type === 'passport' && preg_match('/^[A-Z0-9]{6,20}$/D', $number)) {
            return $number;
        }
        return null;
    }

    public static function fingerprint(string $type, string $normalizedNumber): string
    {
        return hash_hmac('sha256', $type . ':' . $normalizedNumber, self::key());
    }

    private static function key(): string
    {
        $path = dirname(__DIR__, 2) . '/var/identity/key';
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Identity key is unavailable.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('Identity key cannot be locked.');
            }
            rewind($handle);
            $key = stream_get_contents($handle);
            if ($key === false) {
                throw new RuntimeException('Identity key cannot be read.');
            }
            if ($key === '') {
                $key = random_bytes(32);
                rewind($handle);
                if (fwrite($handle, $key) !== 32 || !fflush($handle)) {
                    throw new RuntimeException('Identity key cannot be written.');
                }
                chmod($path, 0600);
            }
            if (strlen($key) !== 32) {
                throw new RuntimeException('Identity key has an invalid length.');
            }
            return $key;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
