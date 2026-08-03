<?php

declare(strict_types=1);

namespace App\Enums;

enum SystemCheckStatus: string
{
    case PASS = 'pass';
    case WARN = 'warn';
    case FAIL = 'fail';

    public function badgeClass(): string
    {
        return match ($this)
        {
            self::PASS => 'bg-success',
            self::WARN => 'bg-warning',
            self::FAIL => 'bg-danger',
        };
    }

    public function icon(): string
    {
        return match ($this)
        {
            self::PASS => 'fa-circle-check',
            self::WARN => 'fa-triangle-exclamation',
            self::FAIL => 'fa-circle-xmark',
        };
    }
}
