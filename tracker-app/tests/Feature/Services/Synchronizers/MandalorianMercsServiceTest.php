<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Models\Organization;
use App\Services\GoogleService;
use App\Services\Synchronizers\MandalorianMercsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MandalorianMercsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_updates_synchronized_at_even_with_no_sync_logic(): void
    {
        $organization = Organization::factory()->create();
        $google = Mockery::mock(GoogleService::class);

        $subject = new MandalorianMercsService($organization, $google);

        $subject->run();

        $organization->refresh();

        $this->assertNotNull($organization->{Organization::SYNCHRONIZED_AT});
    }
}
