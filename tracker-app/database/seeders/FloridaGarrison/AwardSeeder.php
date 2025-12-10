<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\Award;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AwardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacy_awards = DB::table('awards')->get();

        foreach ($legacy_awards as $award)
        {
            $a = Award::find($award->id) ?? new Award(['id' => $award->id]);

            $a->name = $award->title;

            if (str_starts_with($a->name, 'Everglades'))
            {
                $a->organization_id = Organization::where('name', 'Everglades Squad')->first()->id;
            }
            elseif (str_starts_with($a->name, 'Makaze'))
            {
                $a->organization_id = Organization::where('name', 'Makaze Squad')->first()->id;
            }
            else
            {
                $a->organization_id = Organization::where('name', 'Florida Garrison')->first()->id;
            }

            $a->save();
        }
    }
}