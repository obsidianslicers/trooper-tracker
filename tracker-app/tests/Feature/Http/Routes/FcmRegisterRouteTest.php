<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Routes;

use App\Models\MobileDevice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FcmRegisterRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_device_for_trooper(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($trooper)->postJson(route('fcm.register'), ['token' => 'token-a']);

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas(MobileDevice::class, [
            MobileDevice::FCM_TOKEN => 'token-a',
            MobileDevice::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_registers_device_before_setup_is_completed(): void
    {
        // A new trooper's first page in the app is the setup page, which registers the device.
        $trooper = Trooper::factory()->asPending()->create();

        $response = $this->actingAs($trooper)->postJson(route('fcm.register'), ['token' => 'token-a']);

        $response->assertOk();
        $this->assertDatabaseHas(MobileDevice::class, [
            MobileDevice::FCM_TOKEN => 'token-a',
            MobileDevice::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_moves_an_existing_token_to_the_trooper_who_registers_it(): void
    {
        $previous = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $previous->id, MobileDevice::FCM_TOKEN => 'token-a']);

        $this->actingAs($trooper)->postJson(route('fcm.register'), ['token' => 'token-a'])->assertOk();

        $this->assertSame(1, MobileDevice::count());
        $this->assertSame($trooper->id, MobileDevice::first()->trooper_id);
    }

    public function test_rejects_guest(): void
    {
        $response = $this->postJson(route('fcm.register'), ['token' => 'token-a']);

        $response->assertUnauthorized();
        $this->assertSame(0, MobileDevice::count());
    }
}
