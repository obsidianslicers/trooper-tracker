<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\TrooperRequestStatus;
use App\Messages\Troopers\Commands\Membership\DenyTrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperRequestDeniedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class DenyTrooperRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_marks_request_as_denied_and_persists_reason(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper_request = $this->createRequest($trooper, $organization);

        (new DenyTrooperRequest($trooper_request, 'Membership not yet verified.', true))->handle();

        $trooper_request->refresh();

        $this->assertSame(TrooperRequestStatus::DENIED, $trooper_request->status);
        $this->assertSame('Membership not yet verified.', $trooper_request->denial_reason);
        $this->assertNotNull($trooper_request->updated_at);
    }

    public function test_handle_sends_denied_notification_by_default(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper_request = $this->createRequest($trooper, $organization);

        (new DenyTrooperRequest($trooper_request))->handle();

        Notification::assertSentTo($trooper, TrooperRequestDeniedNotification::class);
    }

    public function test_handle_suppresses_denied_notification_when_requested(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper_request = $this->createRequest($trooper, $organization);

        (new DenyTrooperRequest($trooper_request, null, true))->handle();

        Notification::assertNothingSent();
    }

    private function createRequest(Trooper $trooper, Organization $organization): TrooperRequest
    {
        return TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();
    }
}