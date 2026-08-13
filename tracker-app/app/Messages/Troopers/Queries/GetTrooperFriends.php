<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call()
 */
final class GetTrooperFriends extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {
    }

    public function handle(): Collection
    {
        return $this->trooper->trooper_friends()->with('friend')
            ->get()
            ->filter(fn($trooper_friend) => $trooper_friend->friend !== null)
            ->map(fn($trooper_friend) => $trooper_friend->friend)
            ->sort(fn(Trooper $a, Trooper $b): int => strcasecmp($a->display_name, $b->display_name))
            ->values();
    }
}
