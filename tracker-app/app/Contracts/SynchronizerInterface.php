<?php

declare(strict_types=1);

namespace App\Contracts;

interface SynchronizerInterface
{
    public function syncCostumes(): void;

    public function syncAllMembers(): void;

    public function syncMember(string $identifier): void;
}