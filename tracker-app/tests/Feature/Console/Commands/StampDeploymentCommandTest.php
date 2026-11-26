<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Tests\TestCase;

class StampDeploymentCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        @unlink(storage_path('app/deploy.json'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/deploy.json'));
        parent::tearDown();
    }

    public function test_stamps_the_commit_and_status_reports_it(): void
    {
        $this->artisan('tracker:stamp-deployment', ['commit' => 'abc123'])->assertExitCode(0);

        $response = $this->get(route('status'))->assertOk();

        $this->assertSame('abc123', $response->json('commit'));
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/',
            $response->json('deployed_at')
        );
    }
}
