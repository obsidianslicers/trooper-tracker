<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the recruit page where a moderator can directly add any active
 * trooper to one of their moderated units.
 *
 * Authorization is gated on the organization at submission time, not on the
 * trooper being added, which bypasses the circular moderatedBy dependency.
 */
class RecruitController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organizations = Organization::moderatedBy($trooper)
            ->orderBy(Organization::SEQUENCE)
            ->get();

        $root_org_ids = $organizations->map(fn (Organization $org) => $this->getRootOrgId($org))
            ->unique()
            ->toArray();

        $root_orgs = Organization::whereIn(Organization::ID, $root_org_ids)
            ->get([Organization::ID, Organization::NAME, Organization::IDENTIFIER_DISPLAY, Organization::IDENTIFIER_VALIDATION])
            ->keyBy(Organization::ID);

        $organizations_data = $organizations->map(function (Organization $org) use ($root_orgs) {
            $root = $root_orgs[$this->getRootOrgId($org)] ?? null;

            return [
                'id' => $org->id,
                'name' => $org->name,
                'depth' => $org->depth,
                'identifier_display' => $org->identifier_display ?? $root?->identifier_display,
                'identifier_validation' => $org->identifier_validation ?? $root?->identifier_validation,
            ];
        });

        $grouped = $organizations->groupBy(fn (Organization $org) => $this->getRootOrgId($org));

        $data = compact('organizations_data', 'grouped', 'root_orgs');

        return view('pages.admin.troopers.recruit', $data);
    }

    private function getRootOrgId(Organization $org): int
    {
        $parts = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));

        return (int) reset($parts);
    }
}
