<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecord;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRecordDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_service_record_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('service-record.display'));

        $response->assertOk();
        $response->assertViewIs('pages.service-record.display');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-record.display'));

        $response->assertRedirect(route('auth.login'));
    }
}
