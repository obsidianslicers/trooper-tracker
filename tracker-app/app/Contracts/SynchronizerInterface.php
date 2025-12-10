<?php

declare(strict_types=1);

namespace App\Contracts;

interface SynchronizerInterface
{
    public function syncAll(): void;

    public function syncMember(string $identifier): void;
}