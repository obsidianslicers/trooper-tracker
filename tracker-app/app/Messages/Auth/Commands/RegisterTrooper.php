<?php

declare(strict_types=1);

namespace App\Messages\Auth\Commands;

use App\Enums\MembershipRole;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Auth\GuardianAwareness;
use App\Mail\Auth\TrooperRegistered;
use App\Messages\Troopers\Commands\CreateTrooper;
use App\Messages\Troopers\Commands\Membership\CreateTrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Registers a new trooper: creates them with a request to join each organization they picked,
 * then lets them, their guardian and command staff know.
 *
 * @method static Trooper call(string $legal_name, string $display_name, string $email, MembershipRole $membership_role, string|null $password = null, string|null $phone = null, string|null $date_of_birth = null, string|null $guardian_email = null, array $organizations = [])
 */
final class RegisterTrooper extends Message
{
    /**
     * @param  array<int, array<string, mixed>>  $organizations  Keyed by the primary organization's id
     */
    public function __construct(
        private readonly string $legal_name,
        private readonly string $display_name,
        private readonly string $email,
        private readonly MembershipRole $membership_role,
        private readonly ?string $password = null,
        private readonly ?string $phone = null,
        private readonly ?string $date_of_birth = null,
        private readonly ?string $guardian_email = null,
        private readonly array $organizations = [],
    ) {}

    public function handle(): Trooper
    {
        $trooper = DB::transaction(function (): Trooper
        {
            $trooper = $this->createTrooper();

            if ($trooper->membership_role != MembershipRole::HANDLER)
            {
                $this->requestOrganizations($trooper);
            }

            return $trooper;
        });

        $this->announce($trooper);

        return $trooper;
    }

    private function createTrooper(): Trooper
    {
        return CreateTrooper::call(
            legal_name: $this->legal_name,
            display_name: $this->display_name,
            email: $this->email,
            membership_role: $this->membership_role,
            password: $this->password,
            phone: $this->phone,
            date_of_birth: $this->date_of_birth,
            guardian_email: $this->guardian_email,
        );
    }

    private function requestOrganizations(Trooper $trooper): void
    {
        foreach ($this->organizations as $primary_organization_id => $data)
        {
            if (!($data['selected'] ?? false))
            {
                continue;
            }

            //  start with the primary organization as the default assignment
            //  if visitor we keep the primary organization as the assignment
            //  otherwise we resolve the organization based on region and unit
            $organization = Organization::findOrFail($primary_organization_id);

            if ($trooper->membership_role == MembershipRole::MEMBER)
            {
                $organization = $this->resolveOrganization(
                    $organization,
                    isset($data['region_id']) ? (int) $data['region_id'] : null,
                    isset($data['unit_id']) ? (int) $data['unit_id'] : null,
                );
            }

            CreateTrooperRequest::call($trooper, $organization, $data['identifier'] ?? null);
        }
    }

    private function resolveOrganization(Organization $organization, ?int $region_id, ?int $unit_id): Organization
    {
        $region = $organization->organizations()
            ->ofTypeRegions()
            ->firstWhere(Organization::ID, $region_id);

        if ($region->organizations()->count() == 0)
        {
            return $region;
        }

        return $region->organizations()
            ->ofTypeUnits()
            ->firstWhere(Organization::ID, $unit_id);
    }

    private function announce(Trooper $trooper): void
    {
        event(new Registered($trooper));

        Mail::to($trooper->email)->queue(new TrooperRegistered);

        if ($trooper->is_minor)
        {
            Mail::to($trooper->guardian->email)->queue(new GuardianAwareness);
        }

        dispatch(new SendTrooperRegisteredNotificationsJob($trooper));
    }
}
