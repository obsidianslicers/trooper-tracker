<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Models\Organization;
use App\Plugins\PluginSheetSource;
use App\Services\Synchronizers\MandalorianMercsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MandalorianMercsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_updates_synchronized_at_even_with_no_sync_logic(): void
    {
        $organization = Organization::factory()->create();
        $sheets = app(PluginSheetSource::class);

        $subject = new MandalorianMercsService($organization, $sheets);

        $subject->run();

        $organization->refresh();

        $this->assertNotNull($organization->{Organization::SYNCHRONIZED_AT});
    }
}
