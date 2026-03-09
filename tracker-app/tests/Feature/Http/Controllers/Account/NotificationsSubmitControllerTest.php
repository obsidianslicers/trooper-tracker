<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\NotificationFrequency;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_notification_settings_successfully(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post(route('account.notifications'), [
            'notification_frequency' => NotificationFrequency::DAILY->value,
            'organizations' => [
                $organization->id => [
                    'should_notify' => '1',
                ],
            ],
        ]);

        $response->assertRedirect(route('account.notifications'));

        $trooper->refresh();

        $this->assertEquals(NotificationFrequency::DAILY, $trooper->notification_frequency);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(route('account.notifications'), [
            'notification_frequency' => NotificationFrequency::NEVER->value,
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
