<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the form to create a new award.
 */
class CreateController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award creation page.
     *
     * Authorizes the trooper, then prepares an Award instance. If a `copy_id` is
     * present, it copies data from a moderated award. If an `organization_id` is
     * present, it pre-selects that organization within moderation constraints.
     *
     * @param  Request  $request  The incoming HTTP request object.
     * @return View The rendered award creation view.
     */
    public function __invoke(Request $request): View
    {
        $this->authorize('create', Award::class);

        $trooper = $request->user();

        $award = $this->createAward($request, $trooper);

        $this->assignOrganization($request, $award, $trooper);

        $data = compact('award');

        return view('pages.admin.awards.create', $data);
    }

    /**
     * Create a new Award instance, optionally copying data from an existing award.
     *
     * If a `copy_id` is present, it copies data from a moderated award into the
     * new Award instance.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Trooper  $trooper  The authenticated trooper.
     * @return Award The new Award instance.
     */
    private function createAward(Request $request, Trooper $trooper): Award
    {
        $award = new Award;

        // Default for new awards
        $award->frequency = AwardFrequency::ONCE;

        if ($request->has('copy_id'))
        {
            $copy_id = $request->query('copy_id');

            $copy = Award::moderatedBy($trooper)->findOrFail($copy_id);

            if ($copy)
            {
                $award = $copy->replicate();

                $award->name = $copy->name.' (Copy)';
            }
        }

        return $award;
    }

    /**
     * Assign an organization to the award when `organization_id` is provided.
     *
     * Ensures the organization is moderated by the trooper when not an
     * administrator, and throws if the organization cannot be found.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Award  $award  The award being created.
     * @param  Trooper  $trooper  The authenticated trooper.
     * @return void
     */
    private function assignOrganization(Request $request, Award $award, Trooper $trooper)
    {
        if ($request->has('organization_id'))
        {
            $award->organization_id = $request->query('organization_id');
        }

        if ($award->organization_id != null)
        {
            $q = Organization::moderatedBy($trooper);

            $award->organization = $q->findOrFail($award->organization_id);
        }
    }
}
