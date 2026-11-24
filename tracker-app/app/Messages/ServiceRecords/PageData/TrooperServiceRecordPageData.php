<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\Resources\Record\DonationRowResource;
use App\Messages\ServiceRecords\Resources\Record\ServiceRecordShiftResource;
use App\Messages\ServiceRecords\Resources\Record\ServiceRecordTrooperResource;
use App\Messages\ServiceRecords\Resources\Record\ServiceSummaryResource;
use App\Messages\ServiceRecords\Resources\Record\TaggedUploadResource;
use App\Messages\ServiceRecords\Resources\Record\TrooperAwardResource;
use App\Messages\ServiceRecords\Resources\Record\TrooperCostumeResource;
use App\Messages\ServiceRecords\Resources\Record\TrooperOrganizationResource;
use App\Messages\Troopers\Queries\Costumes\GetTrooperCostumesForDisplay;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Messages\Troopers\Queries\GetTrooperServiceRecord;
use App\Models\Costume;
use App\Models\EventShift;
use App\Models\Trooper;
use App\Plugins\PluginDonations;
use App\Plugins\PluginGuard;
use App\Plugins\PluginSlots;
use App\Support\DonationSummary;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

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
    public const PLUGIN_SLOTS = [
        'service-records.trooper.badges',
        'service-records.trooper.actions',
        'service-records.trooper.summary',
        'service-records.trooper.history',
        'service-records.trooper.donations',
    ];

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

        return $this->present($this->withLiveDonationTotals($data, $donations));
    }

    /**
     * Shapes the record into what the Svelte page reads.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function present(array $data): array
    {
        $is_self = $this->actor->id === $this->trooper->id;
        $support_enabled = (bool) config('tracker.support.url');

        return [
            'trooper' => new ServiceRecordTrooperResource(
                $data['trooper'],
                $data['service_summary'],
                $this->actor->can('update', $this->trooper),
                $data['has_missing_credit'],
                $is_self,
            ),
            'summary' => new ServiceSummaryResource(
                $data['trooper'],
                $data['service_summary'],
                $data['club_troop_counts'],
                $data['is_active_donor'],
            ),
            'upcoming_shifts' => $this->shifts($data['upcoming_shifts'], ServiceRecordShiftResource::UPCOMING),
            'recent_shifts' => $this->shifts($data['recent_shifts'], ServiceRecordShiftResource::RECENT),
            'pending_shifts' => $is_self
                ? $this->shifts($data['pending_confirmation_shifts'], ServiceRecordShiftResource::PENDING)
                : [],
            'awards' => TrooperAwardResource::collection($data['awards']),
            'organizations' => TrooperOrganizationResource::collection($data['trooper_organizations']),
            'costumes' => TrooperCostumeResource::collection($data['trooper_costumes']->values()),
            'tagged_uploads' => TaggedUploadResource::collection($data['tagged_uploads']),
            'donations' => $support_enabled ? DonationRowResource::collection($data['all_donations']) : null,
            'donations_total' => $support_enabled && $data['all_donations']->isNotEmpty()
                ? format_number($data['all_donations']->sum('amount'), 2)
                : null,
            'record_slots' => $this->pluginSlots($data),
        ];
    }

    /**
     * @param  Collection<int, EventShift>  $shifts
     * @return list<array<string, mixed>>
     */
    private function shifts(Collection $shifts, string $kind): array
    {
        return $shifts
            ->map(fn (EventShift $shift): array => (new ServiceRecordShiftResource($shift, $kind))->resolve())
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    private function pluginSlots(array $data): array
    {
        $context = [
            'trooper' => $data['trooper'],
            'service_summary' => $data['service_summary'],
            'actor' => $this->actor,
        ];

        return array_filter(app(PluginSlots::class)->renderMany(self::PLUGIN_SLOTS, $context));
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
