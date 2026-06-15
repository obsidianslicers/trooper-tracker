<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\TrooperRequestStatus;
use App\Features\Troopers\Commands\SubmitTrooperRequestCommand;
use App\Features\Troopers\Commands\SubmitTrooperRequestCommandHandler;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Jobs\SendTrooperRequestNotificationsJob;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @see SubmitTrooperRequestCommandHandler
 */
class SubmitTrooperRequestCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_pending_join_request(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        Queue::fake();

        $handler = app(SubmitTrooperRequestCommandHandler::class);
        $handler(new SubmitTrooperRequestCommand($trooper, $organization, 'TK-12345'));

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => 'TK-12345',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
    }

    public function test_invoke_does_not_create_trooper_organization(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        Queue::fake();

        $handler = app(SubmitTrooperRequestCommandHandler::class);
        $handler(new SubmitTrooperRequestCommand($trooper, $organization, null));

        $this->assertDatabaseEmpty('tt_trooper_organizations');
        $this->assertDatabaseEmpty('tt_trooper_assignments');
    }

    public function test_invoke_cancels_other_pending_requests_in_same_primary_club(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $sibling = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:200:')->create();
        $target = Organization::factory()->asRegion()->withParent($root)->withNodePath('100:300:')->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($sibling)
            ->forPrimaryOrganization($root)
            ->create();

        Queue::fake();

        $handler = app(SubmitTrooperRequestCommandHandler::class);
        $handler(new SubmitTrooperRequestCommand($trooper, $target, null));

        $this->assertSoftDeleted('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $sibling->id,
        ]);
    }

    public function test_invoke_does_not_cancel_pending_requests_in_different_primary_clubs(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org_a = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $org_b = Organization::factory()->asOrganization()->withNodePath('200:')->create();

        $sibling_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($org_b)
            ->forPrimaryOrganization($org_b)
            ->create();

        Queue::fake();

        $handler = app(SubmitTrooperRequestCommandHandler::class);
        $handler(new SubmitTrooperRequestCommand($trooper, $org_a, null));

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $sibling_request->id,
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $org_b->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
        $this->assertNull($sibling_request->fresh()->deleted_at);
    }

    public function test_invoke_dispatches_send_notifications_job(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $handler = app(SubmitTrooperRequestCommandHandler::class);
        $handler(new SubmitTrooperRequestCommand($trooper, $organization, 'TK-12345'));

        Queue::assertPushed(SendTrooperRequestNotificationsJob::class);
    }

    public function test_invoke_rejects_identifier_from_another_pending_request(): void
    {
        Queue::fake();

        $existing_trooper = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->withNodePath('100:')
            ->create();

        TrooperRequest::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('1012')
            ->create();

        $this->expectException(DuplicateOrganizationIdentifierException::class);
        $this->expectExceptionMessage('501st Legion TKID 1012 is already assigned to another trooper.');

        try
        {
            app(SubmitTrooperRequestCommandHandler::class)(new SubmitTrooperRequestCommand($trooper, $organization, '1012'));
        }
        finally
        {
            $this->assertDatabaseMissing('tt_trooper_requests', [
                TrooperRequest::TROOPER_ID => $trooper->id,
                TrooperRequest::ORGANIZATION_ID => $organization->id,
                TrooperRequest::IDENTIFIER => '1012',
            ]);
            Queue::assertNotPushed(SendTrooperRequestNotificationsJob::class);
        }
    }
}
