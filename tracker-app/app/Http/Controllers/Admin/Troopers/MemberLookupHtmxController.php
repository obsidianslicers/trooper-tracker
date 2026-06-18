<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\MagicBusController;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use App\Services\MemberLookup\MemberLookupResolver;
use Illuminate\View\View;

class MemberLookupHtmxController extends MagicBusController
{
    public function __invoke(TrooperRequest $trooper_request, MemberLookupResolver $resolver): View
    {
        $identifier = $trooper_request->identifier;
        $primary_org = $trooper_request->primaryOrganization;

        $service = $primary_org ? $resolver->resolve($primary_org) : null;

        if (empty($identifier))
        {
            return view('pages.admin.troopers.partials.member-lookup', [
                'identifier'         => null,
                'org_name'           => $primary_org?->name,
                'has_lookup_service' => $service !== null,
                'duplicate'          => null,
                'member'             => null,
            ]);
        }

        $duplicate_trooper = $this->findDuplicate($trooper_request);

        $member = $service?->lookup($identifier);

        return view('pages.admin.troopers.partials.member-lookup', [
            'identifier'         => $identifier,
            'org_name'           => $primary_org?->name,
            'has_lookup_service' => $service !== null,
            'duplicate'          => $duplicate_trooper,
            'member'             => $member,
        ]);
    }

    private function findDuplicate(TrooperRequest $trooper_request): ?\App\Models\Trooper
    {
        $identifier = $trooper_request->identifier;
        $primary_org_id = $trooper_request->primary_organization_id;
        $requesting_trooper_id = $trooper_request->trooper_id;

        $existing_request = TrooperRequest::where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $primary_org_id)
            ->where(TrooperRequest::IDENTIFIER, $identifier)
            ->where(TrooperRequest::TROOPER_ID, '!=', $requesting_trooper_id)
            ->with('trooper')
            ->first();

        if ($existing_request !== null)
        {
            return $existing_request->trooper;
        }

        $existing_org = TrooperOrganization::where(TrooperOrganization::IDENTIFIER, $identifier)
            ->where(TrooperOrganization::TROOPER_ID, '!=', $requesting_trooper_id)
            ->whereHas('organization', function ($q) use ($primary_org_id) {
                $q->where('id', $primary_org_id)
                  ->orWhere('parent_id', $primary_org_id)
                  ->orWhereHas('parent', fn($q2) => $q2->where('parent_id', $primary_org_id));
            })
            ->with('trooper')
            ->first();

        return $existing_org?->trooper;
    }
}
