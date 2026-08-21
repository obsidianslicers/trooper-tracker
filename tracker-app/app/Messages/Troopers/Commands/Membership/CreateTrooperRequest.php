<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Enums\TrooperRequestStatus;
use App\Jobs\SendTrooperRequestNotificationsJob;
use App\Messages\Troopers\Queries\Membership\IsOrganizationIdentifierAvailable;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Hyperdrive\Message;
use Illuminate\Validation\ValidationException;

/**
 * Handler for creating a trooper's club join request.
 *
 * @method static TrooperRequest call(Trooper $trooper, Organization $organization, string|null $identifier = null)
 */
final class CreateTrooperRequest extends Message
{
    private readonly Organization $primary_organization;

    public function __construct(
        private readonly Trooper $trooper,
        private readonly Organization $organization,
        private readonly ?string $identifier = null
    ) {
        $this->primary_organization = $organization->getPrimaryClub();
    }

    public function handle(): TrooperRequest
    {
        $identifier_available = IsOrganizationIdentifierAvailable::call(
            primary_organization: $this->primary_organization,
            identifier: $this->identifier,
            ignore_trooper_id: $this->trooper->id
        );

        if (!$identifier_available)
        {
            $label = $this->organization->identifier_display ?? 'identifier';

            $msg = "{$this->organization->name} {$label} {$this->identifier} is already assigned to another trooper.";

            //  both organizations & organization_id are used in the validation error
            //  display, so we need to set both dpending on whether registration or
            //  account management.
            throw ValidationException::withMessages([
                'organizations' => $msg,
                'organization_id' => $msg,
            ]);
        }

        DeleteTrooperRequests::call(
            trooper: $this->trooper,
            primary_organization: $this->primary_organization,
        );

        $trooper_request = new TrooperRequest;

        $trooper_request->trooper_id = $this->trooper->id;
        $trooper_request->organization_id = $this->organization->id;
        $trooper_request->primary_organization_id = $this->primary_organization->id;
        $trooper_request->identifier = $this->identifier;
        $trooper_request->status = TrooperRequestStatus::PENDING;

        $trooper_request->save();

        SendTrooperRequestNotificationsJob::dispatch($trooper_request);

        return $trooper_request;
    }
}
