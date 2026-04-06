<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TrooperFriend;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateTrooperFriendshipJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $trooper_id, public readonly int $friend_id) {}

    public function handle(): void
    {
        if ($this->trooper_id === $this->friend_id)
        {
            return;
        }

        TrooperFriend::query()->firstOrCreate([
            TrooperFriend::TROOPER_ID => $this->trooper_id,
            TrooperFriend::FRIEND_ID => $this->friend_id,
        ]);

        TrooperFriend::query()->firstOrCreate([
            TrooperFriend::TROOPER_ID => $this->friend_id,
            TrooperFriend::FRIEND_ID => $this->trooper_id,
        ]);
    }
}
