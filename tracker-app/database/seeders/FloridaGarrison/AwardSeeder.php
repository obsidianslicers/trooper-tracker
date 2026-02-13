<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Enums\AwardFrequency;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AwardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacy_awards = DB::table('awards')->orderBy('title')->get();

        foreach ($legacy_awards as $legacy_award)
        {
            // ---------------------------------------------------------------
            //
            $search_name = 'Everglades Rookies of the Year';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::ANNUALLY,
                    has_multiple_recipients: true,
                    name: $search_name
                );

                $award_date = $this->getAnnualDate($legacy_award->title);

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Everglades Trooper of the Month';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::MONTHLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $award_date = $this->getMonthDate($legacy_award->title);

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Everglades Trooper of the Quarter';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::QUARTERLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $this->migrateTroopers($award, $legacy_award);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Everglades Trooper of the Year';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::ANNUALLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $award_date = $this->getAnnualDate($legacy_award->title);

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Makaze Trooper of the Month';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::MONTHLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $award_date = $this->getMonthDate($legacy_award->title);

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Makaze Squad Trooper of the Year';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::ANNUALLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $this->migrateTroopers($award, $legacy_award);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Troop Tracker Contributor';

            if (str_ends_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::ANNUALLY,
                    has_multiple_recipients: true,
                    name: $search_name
                );

                $award_date = $this->getAnnualDate($legacy_award->title);

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Friend of the Legion';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::RANDOM,
                    has_multiple_recipients: true,
                    name: $search_name
                );

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Trooper of the Month';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::MONTHLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $this->migrateTroopers($award, $legacy_award);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Trooper of the Quarter';

            if (str_starts_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::QUARTERLY,
                    has_multiple_recipients: false,
                    name: $search_name
                );

                $this->migrateTroopers($award, $legacy_award);

                continue;
            }

            // ---------------------------------------------------------------
            //
            $search_name = 'Just A Trooper!';

            if (str_ends_with($legacy_award->title, $search_name))
            {
                $award = Award::where(Award::NAME, $search_name)->first() ?? new Award();

                $this->ensureExistance(
                    award: $award,
                    legacy_award: $legacy_award,
                    frequency: AwardFrequency::RANDOM,
                    has_multiple_recipients: true,
                    name: $search_name
                );

                $this->migrateTroopers($award, $legacy_award, $award_date);

                continue;
            }

            // ---------------------------------------------------------------
            // For all other awards, just migrate as-is
            //
            $award = new Award();

            $award->name = $legacy_award->title;
            $award->frequency = AwardFrequency::ONCE;

            $this->assignOrganization($award);

            $award->save();

            $this->migrateTroopers($award, $legacy_award);
        }
    }

    private function ensureExistance(Award $award, object $legacy_award, AwardFrequency $frequency, bool $has_multiple_recipients = false, string $name = null): void
    {
        if (!$award->exists)
        {
            $award->name = $name ?? $legacy_award->title;
            $award->frequency = $frequency;
            $award->has_multiple_recipients = $has_multiple_recipients;

            $this->assignOrganization($award);

            $award->save();
        }
    }

    private function assignOrganization(Award $award)
    {
        if (str_starts_with($award->name, 'Everglades'))
        {
            $award->organization_id = $this->getOrganization('Everglades Squad')->id;
        }
        elseif (str_starts_with($award->name, 'Makaze'))
        {
            $award->organization_id = $this->getOrganization('Makaze Squad')->id;
        }
        else
        {
            $award->organization_id = $this->getOrganization('Florida Garrison')->id;
        }
    }

    private function migrateTroopers(Award $new_award, object $legacy_award, ?Carbon $award_date = null): void
    {
        $legacy_troopers = DB::table('award_troopers')
            ->join('tt_troopers', 'award_troopers.trooperid', '=', 'tt_troopers.id')
            ->where('award_troopers.awardid', $legacy_award->id)
            ->select('award_troopers.*')
            ->get();

        foreach ($legacy_troopers as $trooper)
        {
            $normalized_date = $new_award->frequency->normalizeDate($award_date ?? $trooper->awarded);

            $award_trooper = AwardTrooper::where(AwardTrooper::AWARD_ID, $new_award->id)
                ->where(AwardTrooper::TROOPER_ID, $trooper->trooperid)
                ->where(AwardTrooper::AWARD_DATE, $normalized_date)
                ->first();

            if (!$award_trooper)
            {
                $award_trooper = new AwardTrooper();

                $award_trooper->award_id = $new_award->id;
                $award_trooper->trooper_id = $trooper->trooperid;
                $award_trooper->award_date = $normalized_date;
                $award_trooper->created_at = $trooper->awarded;
                $award_trooper->updated_at = $trooper->awarded;

                $award_trooper->save();
            }
        }
    }

    private function getOrganization(string $name): Organization
    {
        $organizations = once(fn() => Organization::all()->keyBy('name'));

        return $organizations[$name];
    }

    private function getAnnualDate(string $title): Carbon
    {
        if (preg_match('/(\d{4})/', $title, $match))
        {
            $year = $match[1];

            $date = Carbon::createFromDate($year, 1, 1);

            return $date;
        }

        throw new Exception('Unable to determine date for annual award: ' . $title);
    }

    private function getMonthDate(string $title): Carbon
    {
        if (preg_match('/([A-Za-z]+)\s+(\d{4})$/', $title, $match))
        {
            $month = $match[1];
            $year = $match[2];

            $date = Carbon::createFromFormat('F Y', "$month $year");

            return $date;
        }

        throw new Exception('Unable to determine date for monthly award: ' . $title);
    }
}