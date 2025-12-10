<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\EventUploadTrooper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventUploadTrooperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legacy_tags = DB::table('tagged')
            ->join('tt_event_uploads', 'tagged.photoid', '=', 'tt_event_uploads.id')
            ->join('tt_troopers', 'tagged.trooperid', '=', 'tt_troopers.id')
            ->select('tagged.*')
            ->get();

        foreach ($legacy_tags as $tag)
        {
            $e = EventUploadTrooper::find($tag->id) ?? new EventUploadTrooper(['id' => $tag->id]);

            $e->event_upload_id = $tag->photoid;
            $e->trooper_id = $tag->trooperid;

            $e->save();
        }
    }
}