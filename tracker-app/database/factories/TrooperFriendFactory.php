<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Trooper;
use App\Models\TrooperFriend;
use Database\Factories\Base\TrooperFriendFactory as BaseTrooperFriendFactory;

class TrooperFriendFactory extends BaseTrooperFriendFactory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            TrooperFriend::TROOPER_ID => Trooper::factory(),
            TrooperFriend::FRIEND_ID => Trooper::factory(),
        ];
    }

    public function forTrooper(Trooper $trooper): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperFriend::TROOPER_ID => $trooper->{Trooper::ID},
        ]);
    }

    public function forFriend(Trooper $friend): static
    {
        return $this->state(fn(array $attributes): array => [
            TrooperFriend::FRIEND_ID => $friend->{Trooper::ID},
        ]);
    }
}
