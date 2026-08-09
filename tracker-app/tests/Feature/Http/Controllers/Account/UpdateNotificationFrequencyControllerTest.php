<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\NotificationFrequency;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateNotificationFrequencyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_notification_frequency_and_renders_account_index(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withNotificationFrequency(NotificationFrequency::NEVER)
            ->create();

        $response = $this->actingAs($trooper)->post(route('account.update-notification-frequency'), [
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY->value,
        ]);

        $trooper->refresh();

        $response->assertOk();
        $response->assertViewIs('layouts.inertia');
        $response->assertInertia(fn(Assert $page) => $page
            ->component('account/Index')
        );

        $this->assertSame(NotificationFrequency::DAILY, $trooper->{Trooper::NOTIFICATION_FREQUENCY});
    }
}
