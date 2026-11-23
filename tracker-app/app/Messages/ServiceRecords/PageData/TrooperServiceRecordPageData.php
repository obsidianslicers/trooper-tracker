<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\Trooper;
use App\Plugins\PluginDonations;
use App\Plugins\PluginGuard;
use App\Support\DonationSummary;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * A trooper's service record: their troops, costumes, awards and donations.
 *
 * Donation totals add what each donation plugin reports for the trooper to the donations on
 * record.
 *
 * @method static array<string, mixed> call(Trooper $trooper, Actor $actor)
 */
final class TrooperServiceRecordPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Actor $actor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(PluginDonations $donations): array
    {
        $data = GetTrooperServiceRecord::call(trooper_id: $this->trooper->id);

        $data['trooper_costumes'] = GetTrooperCostumesForDisplay::call(trooper: $data['trooper'])
            ->filter(fn ($c) => !in_array($c->name, [Costume::COMMAND_STAFF, Costume::HANDLER]));

        $data['has_missing_credit'] = $this->actor->can('update', $this->trooper) && $this->hasMissingCredit();

        return $this->withLiveDonationTotals($data, $donations);
    }

    private function hasMissingCredit(): bool
    {
        ['total' => $total] = GetEventTroopersMissingCredit::call(
            actor: $this->actor,
            trooper_id: $this->trooper->id,
        );

        return $total > 0;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withLiveDonationTotals(array $data, PluginDonations $donations): array
    {
        $month_keys = $data['all_donations']
            ->filter(fn ($d) => $d->created_at !== null)
            ->mapWithKeys(fn ($d) => [$d->created_at->format('Y-m') => true])
            ->all();
        $total = (float) $data['all_donations']->sum('amount');
        $is_active_donor = false;

        foreach ($donations->sources() as $source)
        {
            $summary = PluginGuard::attempt(
                'summarize donations',
                fn (): ?DonationSummary => $source->summaryFor($this->trooper),
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

        return $data;
    }
}
