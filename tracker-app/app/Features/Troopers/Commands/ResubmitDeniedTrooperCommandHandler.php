<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Jobs\SendTrooperResubmittedNotificationsJob;
use App\Models\Organization;
use App\Models\TrooperRequest;

readonly class ResubmitDeniedTrooperCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  ResubmitDeniedTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper = $message->trooper;

        $trooper->membership_status = MembershipStatus::PENDING;
        $trooper->save();

        TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->denied()
            ->delete();

        if ($trooper->membership_role !== MembershipRole::HANDLER)
        {
            foreach ($message->organizations as $org_id => $data)
            {
                if (empty($data['selected']))
                {
                    continue;
                }

                $organization = Organization::find((int) $org_id);

                if (!$organization)
                {
                    continue;
                }

                $resolved = $this->resolveOrganization($data, $organization, $trooper->membership_role);

                if ($resolved === null)
                {
                    continue;
                }

                $primary = $resolved->getPrimaryClub();
                $identifier = isset($data['identifier']) ? (trim((string) $data['identifier']) ?: null) : null;

                TrooperRequest::create([
                    TrooperRequest::TROOPER_ID => $trooper->id,
                    TrooperRequest::ORGANIZATION_ID => $resolved->id,
                    TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary->id,
                    TrooperRequest::IDENTIFIER => $identifier,
                    TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
                ]);
            }
        }

        SendTrooperResubmittedNotificationsJob::dispatch($trooper);

        return null;
    }

    private function resolveOrganization(array $data, Organization $organization, ?MembershipRole $role): ?Organization
    {
        if ($role?->isVisitor())
        {
            return $organization;
        }

        if (!isset($data['region_id']))
        {
            return null;
        }

        $region = $organization->organizations()
            ->ofTypeRegions()
            ->firstWhere(Organization::ID, $data['region_id']);

        if (!$region)
        {
            return null;
        }

        if ($region->organizations()->count() === 0)
        {
            return $region;
        }

        if (!isset($data['unit_id']))
        {
            return null;
        }

        return $region->organizations()
            ->ofTypeUnits()
            ->firstWhere(Organization::ID, $data['unit_id']);
    }
}
