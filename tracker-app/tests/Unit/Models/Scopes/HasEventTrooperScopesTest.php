<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Scopes;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventTrooperScopesTest extends TestCase
{
    use RefreshDatabase;

}
