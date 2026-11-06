<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Models\Organization;
use App\Plugins\PluginSheetSource;
use App\Services\Synchronizers\DarkEmpireServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DarkEmpireServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_updates_synchronized_at_even_with_no_sync_logic(): void
    {
        $organization = Organization::factory()->create();
        $sheets = app(PluginSheetSource::class);

        $subject = new DarkEmpireServices($organization, $sheets);

        $subject->run();

        $organization->refresh();

        $this->assertNotNull($organization->{Organization::SYNCHRONIZED_AT});
    }
}
