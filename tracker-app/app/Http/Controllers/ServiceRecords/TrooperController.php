<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Http\Controllers\PageController;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Trooper;
use App\Plugins\PluginDonations;
use App\Plugins\PluginGuard;
use App\Support\DonationSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

/**
 * Displays a trooper's service record dashboard.
 */
class TrooperController extends PageController
{
    /**
     * Retrieves dashboard data and renders the trooper service record view.
     *
     * Filters command staff and handler costumes from the displayed costume list.
     *
     * @throws RuntimeException
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        if ($trooper->id == Auth::user()->id)
        {
            $this->crumbs->addRoute('Profile', 'account.index');
        }

        $data = GetTrooperServiceRecord::call(trooper_id: $trooper->id);

        $trooper_costumes = GetTrooperCostumesForDisplay::call(trooper: $data['trooper']);

        $trooper_costumes = $trooper_costumes->filter(fn ($c) => !in_array($c->name, [Costume::COMMAND_STAFF, Costume::HANDLER]));

        $data['trooper_costumes'] = $trooper_costumes;
        $data['has_missing_credit'] = Auth::user()->can('update', $trooper) && $this->hasMissingCredit($trooper);
        $this->applyLiveDonationTotals($data, $trooper);

        return view('pages.service-records.trooper', $data);
    }

    private function hasMissingCredit(Trooper $trooper): bool
    {
        ['total' => $total] = GetEventTroopersMissingCredit::call(actor: Auth::user(), trooper_id: $trooper->id);

        return $total > 0;
    }

    /**
     * Live totals from local records and every donation source, so the profile is
     * current whenever the batch recalculation last ran.
     *
     * @param  array<string, mixed>  $data
     */
    private function applyLiveDonationTotals(array &$data, Trooper $trooper): void
    {
        $month_keys = $data['all_donations']
            ->filter(fn ($d) => $d->created_at !== null)
            ->mapWithKeys(fn ($d) => [$d->created_at->format('Y-m') => true])
            ->all();
        $total = (float) $data['all_donations']->sum('amount');
        $is_active_donor = false;

        foreach (app(PluginDonations::class)->sources() as $source)
        {
            $summary = PluginGuard::attempt(
                'summarize donations',
                fn (): ?DonationSummary => $source->summaryFor($trooper),
            );

            if ($summary !== null)
            {
                $month_keys = array_merge($month_keys, $summary->month_keys);
                $total += $summary->total;
                $is_active_donor = $is_active_donor || $summary->is_active;
            }
        }

        $data['is_active_donor'] = $is_active_donor;
        $data['service_summary']['total_donated'] = $total;
        $data['service_summary']['donation_months'] = count($month_keys);
    }
}
