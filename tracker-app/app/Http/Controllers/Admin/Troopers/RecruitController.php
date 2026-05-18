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

    /**
     * @param  Request  $request
     * @return View
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $organizations = Organization::moderatedBy($trooper)
            ->ofTypeUnits()
            ->orderBy(Organization::SEQUENCE)
            ->get();

        $data = compact('organizations');

        return view('pages.admin.troopers.recruit', $data);
    }
}
