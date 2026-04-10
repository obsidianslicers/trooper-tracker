<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison;

use App\Models\EventTrooper;
use App\Models\TrooperFriend;
use Illuminate\Database\Seeder;

class TrooperFriendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $troopers_added = EventTrooper::query()
            ->select(EventTrooper::TROOPER_ID, EventTrooper::ADDED_BY_TROOPER_ID)
            ->whereNotNull(EventTrooper::ADDED_BY_TROOPER_ID)
            ->distinct()
            ->get();

        foreach ($troopers_added as $trooper_added)
        {
            // feels backwards but if 'trooper_id' was added by 'added_by_trooper_id' to an
            // event, we want to create a friend relationship where 'added_by_trooper_id' is
            // the friend of 'trooper_id'
            $trooper_friend = TrooperFriend::query()
                ->where('trooper_id', $trooper_added->added_by_trooper_id)
                ->where('friend_id', $trooper_added->trooper_id)
                ->first();
            if ($trooper_friend == null)
            {
                $trooper_friend = new TrooperFriend();
                $trooper_friend->trooper_id = $trooper_added->added_by_trooper_id;
                $trooper_friend->friend_id = $trooper_added->trooper_id;
                $trooper_friend->save();
            }

            // also create the inverse relationship to ensure friendship is mutual
            $inverse_friend = TrooperFriend::query()
                ->where('trooper_id', $trooper_added->trooper_id)
                ->where('friend_id', $trooper_added->added_by_trooper_id)
                ->first();
            if ($inverse_friend == null)
            {
                $inverse_friend = new TrooperFriend();
                $inverse_friend->trooper_id = $trooper_added->trooper_id;
                $inverse_friend->friend_id = $trooper_added->added_by_trooper_id;
                $inverse_friend->save();
            }
        }
    }
}