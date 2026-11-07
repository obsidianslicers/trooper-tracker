<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Controllers\PageController;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class AuthorityController
 *
 * Handles the display of a trooper's authority management page.
 */
class AuthorityController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's authority page
     *
     * This method authorizes the user, sets up breadcrumbs, and returns the view
     * for managing a specific trooper's roles and organizational assignments.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper whose authorities are to be displayed
     * @return View The rendered authority page view
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('updateAuthority', $trooper);

        $organization_authorities = Organization::withAllAssignments($trooper)->get();

        $data = [
            'trooper' => $trooper,
            'organization_authorities' => $organization_authorities,
            'role_options' => $this->roleOptions($request),
        ];

        return view('pages.admin.troopers.authority', $data);
    }

    /**
     * @return array<string, string>
     */
    private function roleOptions(Request $request): array
    {
        $options = MembershipRole::toArray();

        if (!$request->user()->has_system_owner)
        {
            unset($options[MembershipRole::ADMINISTRATOR->value]);
        }

        return $options;
    }
}
