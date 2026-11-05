<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\TrooperRequestStatus;
use App\Jobs\SendTrooperRequestNotificationsJob;
use App\Messages\Troopers\Queries\Membership\AssertOrganizationIdentifierAvailable;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Hyperdrive\Message;

/**
 * Command to submit a club join request on behalf of a trooper.
 *
 * Creates a pending TrooperRequest record for later approval.
 * Moderators of the target organization are notified by the handler.
 *
 * @method static void call(Trooper $trooper, Organization $organization, ?string $identifier)
 */
final class SubmitTrooperRequest extends Message
{
    /**
     * @param  Trooper  $trooper  The trooper requesting membership
     * @param  Organization  $organization  The leaf-node organization to join
     * @param  string|null  $identifier  Optional club-specific identifier (e.g., TK number)
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
        private readonly ?string $identifier,
    ) {}

    public function handle(): void
    {
        $organization = $this->organization;
        $trooper = $this->trooper;
        $primary_club = $organization->getPrimaryClub();

        AssertOrganizationIdentifierAvailable::call(
            primary_organization: $primary_club,
            identifier: $this->identifier,
            ignore_trooper_id: $trooper->id,
        );

        // Cancel any other pending request in this primary-club family.
        TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $primary_club->id)
            ->delete();

        $trooper_request = TrooperRequest::create([
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary_club->id,
            TrooperRequest::IDENTIFIER => $this->identifier ?: null,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);

        SendTrooperRequestNotificationsJob::dispatch($trooper_request);
    }
}
