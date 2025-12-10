<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Contracts\SynchronizerInterface;
use App\Models\Organization;

abstract class BaseOrganizationService implements SynchronizerInterface
{
    public function __construct(protected readonly Organization $organization)
    {
    }

    public abstract function syncAll(): void;

    public abstract function syncMember(string $identifier): void;

    protected function cleanInput($value): mixed
    {
        $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return $value;
    }
}