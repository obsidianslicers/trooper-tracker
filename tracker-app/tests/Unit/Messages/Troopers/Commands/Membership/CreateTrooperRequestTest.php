<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Membership;

use App\Enums\TrooperRequestStatus;
use App\Jobs\SendTrooperRequestNotificationsJob;
use App\Messages\Troopers\Commands\Membership\CreateTrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateTrooperRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_pending_request_and_dispatches_notifications(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $organization = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->withNodePath('100:200:')
            ->create();

        $subject = new CreateTrooperRequest($trooper, $organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $result->id,
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary_organization->id,
            TrooperRequest::IDENTIFIER => 'TK-12345',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);

        Queue::assertPushed(SendTrooperRequestNotificationsJob::class);
    }

    public function test_handle_soft_deletes_existing_pending_request_in_same_primary_club(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $sibling_organization = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->withNodePath('100:200:')
            ->create();
        $target_organization = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->withNodePath('100:300:')
            ->create();

        $pending_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($sibling_organization)
            ->forPrimaryOrganization($primary_organization)
            ->asPending()
            ->create();

        $subject = new CreateTrooperRequest($trooper, $target_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertSoftDeleted('tt_trooper_requests', [
            TrooperRequest::ID => $pending_request->id,
        ]);

        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::ID => $result->id,
            TrooperRequest::ORGANIZATION_ID => $target_organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary_organization->id,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
    }

    public function test_handle_throws_validation_exception_when_identifier_is_unavailable(): void
    {
        Queue::fake();

        $existing_trooper = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('12345')
            ->create();

        $subject = new CreateTrooperRequest($trooper, $primary_organization, '12345');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('501st Legion TKID 12345 is already assigned to another trooper.');

        try
        {
            $subject->handle();
        }
        finally
        {
            $this->assertDatabaseMissing('tt_trooper_requests', [
                TrooperRequest::TROOPER_ID => $trooper->id,
                TrooperRequest::ORGANIZATION_ID => $primary_organization->id,
                TrooperRequest::IDENTIFIER => '12345',
            ]);
            Queue::assertNotPushed(SendTrooperRequestNotificationsJob::class);
        }
    }
}