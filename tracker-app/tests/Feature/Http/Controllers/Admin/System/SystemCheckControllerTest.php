<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\System;

use App\Models\Trooper;
use App\Services\LogEntry;
use App\Services\LogViewerService;
use App\Services\SystemCheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SystemCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sends_log_entries_without_validation_errors(): void
    {
        $system_owner = Trooper::factory()->asSystemOwner()->create();
        $this->mock(SystemCheckService::class)->shouldReceive('run')->andReturn(collect());
        $this->mock(LogViewerService::class)->shouldReceive('recentErrors')->andReturn(collect([
            new LogEntry('2026-11-22 10:00:00', 'production', 'error', 'Boom'),
        ]));

        $response = $this->actingAs($system_owner)->get(route('admin.system-check'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/SystemCheck')
            ->where('log_entries.0.summary', 'Boom')
            ->where('has_more_errors', false)
            ->where('errors', []));
    }

    public function test_invoke_shows_more_errors_when_asked(): void
    {
        $system_owner = Trooper::factory()->asSystemOwner()->create();
        $this->mock(SystemCheckService::class)->shouldReceive('run')->andReturn(collect());
        $this->mock(LogViewerService::class)->shouldReceive('recentErrors')->once()->with(51)->andReturn(collect());

        $response = $this->actingAs($system_owner)->get(route('admin.system-check', ['errors' => 50]));

        $response->assertOk();
    }
}
