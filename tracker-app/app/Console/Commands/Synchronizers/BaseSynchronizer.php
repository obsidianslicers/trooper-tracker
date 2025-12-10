<?php

declare(strict_types=1);

namespace App\Console\Commands\Synchronizers;

use App\Models\Organization;
use Illuminate\Console\Command;

abstract class BaseSynchronizer
{
    protected readonly Organization $organization;

    public function __construct(protected readonly Command $command, string $organization_name)
    {
        $this->organization = $this->getOrganization($organization_name);
    }

    public abstract function run(): void;

    private function getOrganization(string $name): Organization
    {
        return Organization::where(Organization::NAME, $name)->first();
    }
}