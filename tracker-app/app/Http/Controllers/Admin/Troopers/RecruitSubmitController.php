<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\DirectAddTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Handles submission of the recruit form.
 *
 * Validates trooper and organization, authorizes against the organization
 * (not the trooper being added), then dispatches DirectAddTrooperCommand.
 */
class RecruitSubmitController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->validate([
            'trooper_id'      => ['required', 'integer', Rule::exists('tt_troopers', 'id')],
            'organization_id' => ['required', 'integer', Rule::exists('tt_organizations', 'id')],
        ]);

        /** @var Organization $organization */
        $organization = Organization::findOrFail($request->integer('organization_id'));

        $this->authorize('recruit', $organization);

        /** @var Trooper $subject */
        $subject = Trooper::findOrFail($request->integer('trooper_id'));

        $request->validate([
            'identifier' => $this->buildIdentifierRules($organization, $subject),
        ]);

        $this->bus->send(new DirectAddTrooperCommand(
            $subject,
            $organization,
            $request->filled('identifier') ? $request->string('identifier')->toString() : null,
        ));

        return redirect()
            ->route('admin.troopers.recruit')
            ->with('flash_message', json_encode([
                'message' => "{$subject->display_name} has been added to {$organization->name}.",
                'type'    => 'success',
            ]));
    }

    private function buildIdentifierRules(Organization $organization, Trooper $trooper): array
    {
        if (empty($organization->identifier_validation)) {
            return ['nullable', 'string', 'max:64'];
        }

        return array_merge(
            ['nullable'],
            explode('|', $organization->identifier_validation),
            [new UniqueOrganizationIdentifierRule($organization, $trooper)],
        );
    }
}
