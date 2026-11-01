<?php

declare(strict_types=1);

namespace App\Services;

class PhpIniReader
{
    public function get(string $key): string
    {
        $value = ini_get($key);

        return $value === false ? '' : $value;
    }

    public function bytes(string $key): int
    {
        $value = trim($this->get($key));

        return $value === '' ? 0 : ini_parse_quantity($value);
    }
}
