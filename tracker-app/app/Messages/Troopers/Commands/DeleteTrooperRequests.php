<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\TrooperRequestStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Hyperdrive\Message;

/**
 * Handler for deleting a trooper's club join request.
 *
 * @method static void call(Trooper $trooper, Organization $primary_organization)
 */
final class DeleteTrooperRequests extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $primary_organization
    ) {}

    public function handle(): void
    {
        // Cancel any other pending request in this primary-club family.
        TrooperRequest::query()
            ->where(TrooperRequest::TROOPER_ID, $this->trooper->id)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $this->primary_organization->id)
            ->delete();
    }
}
