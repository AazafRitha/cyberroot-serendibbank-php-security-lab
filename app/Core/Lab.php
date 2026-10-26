<?php

declare(strict_types=1);

namespace CyberRoot\SerendibBank\Core;

final class Lab
{
    public static function enabled(): bool
    {
        return getenv('APP_LAB_MODE') === '1';
    }
}
