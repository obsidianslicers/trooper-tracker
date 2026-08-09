<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdatePushNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_push_notifications_and_renders_account_index(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->create([
                Trooper::PUSH_NOTIFICATIONS_ENABLED => false,
            ]);

        $response = $this->actingAs($trooper)->post(route('account.update-push-notifications'), [
            Trooper::PUSH_NOTIFICATIONS_ENABLED => true,
        ]);

        $trooper->refresh();

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
        );

        $this->assertTrue($trooper->{Trooper::PUSH_NOTIFICATIONS_ENABLED});
    }
}
