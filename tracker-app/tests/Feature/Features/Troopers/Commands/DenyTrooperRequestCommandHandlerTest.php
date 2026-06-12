<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\TrooperRequestStatus;
use App\Features\Troopers\Commands\DenyTrooperRequestCommand;
use App\Features\Troopers\Commands\DenyTrooperRequestCommandHandler;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Notifications\Troopers\TrooperRequestDeniedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * @see DenyTrooperRequestCommandHandler
 */
class DenyTrooperRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_marks_join_request_as_denied(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyTrooperRequestCommandHandler::class);
        $handler(new DenyTrooperRequestCommand($trooper_request));

        $trooper_request->refresh();
        $this->assertEquals(TrooperRequestStatus::DENIED, $trooper_request->status);
        $this->assertNotNull($trooper_request->updated_at);
    }

    public function test_invoke_persists_denial_reason_on_the_record(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyTrooperRequestCommandHandler::class);
        $handler(new DenyTrooperRequestCommand($trooper_request, 'Membership not yet verified.'));

        $trooper_request->refresh();
        $this->assertEquals('Membership not yet verified.', $trooper_request->denial_reason);
    }

    public function test_invoke_does_not_create_membership_or_assignment(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyTrooperRequestCommandHandler::class);
        $handler(new DenyTrooperRequestCommand($trooper_request));

        $this->assertDatabaseEmpty('tt_trooper_organizations');
        $this->assertDatabaseEmpty('tt_trooper_assignments');
    }

    public function test_invoke_sends_denied_notification_to_trooper(): void
    {
        Notification::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $handler = app(DenyTrooperRequestCommandHandler::class);
        $handler(new DenyTrooperRequestCommand($trooper_request));

        Notification::assertSentTo($trooper, TrooperRequestDeniedNotification::class);
    }
}
