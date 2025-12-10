<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\AwardTrooper;
use Database\Seeders\FloridaGarrison\Traits\HasEnumMaps;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AwardTrooperSeeder extends Seeder
{
    use HasEnumMaps;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacy_trooper_awards = DB::table('award_troopers')
            ->join('tt_troopers', 'award_troopers.trooperid', '=', 'tt_troopers.id')
            ->select('award_troopers.*')
            ->get();

        foreach ($legacy_trooper_awards as $award)
        {
            $a = AwardTrooper::find($award->id) ?? new AwardTrooper(['id' => $award->id]);

            $a->award_id = $award->awardid;
            $a->trooper_id = $award->trooperid;

            $a->award_date = $award->awarded;
            $a->created_at = $award->awarded;
            $a->updated_at = $award->awarded;

            $a->save();
        }
    }
}