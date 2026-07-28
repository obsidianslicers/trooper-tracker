<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\Trooper;
use App\Models\TrooperFriend;
use Hyperdrive\Message;

/**
 * Merges the friends of two troopers.
 * This command ensures that all friendships of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeTrooperFriends extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {}

    public function handle(): void
    {
        $this->mergeOutgoingFriendships();
        $this->mergeIncomingFriendships();
    }

    private function mergeOutgoingFriendships(): void
    {
        $source_friendships = TrooperFriend::query()
            ->withTrashed()
            ->where(TrooperFriend::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(TrooperFriend::ID)
            ->get();

        foreach ($source_friendships as $source_friendship)
        {
            if ($source_friendship->friend_id === $this->target_trooper->id)
            {
                $source_friendship->forceDelete();

                continue;
            }

            $target_friendship = $this->getTargetFriendship($source_friendship->friend_id);

            if ($target_friendship === null)
            {
                $source_friendship->trooper_id = $this->target_trooper->id;
                $source_friendship->save();

                continue;
            }

            if ($target_friendship->trashed() && !$source_friendship->trashed())
            {
                $target_friendship->restore();
            }

            $source_friendship->forceDelete();
        }
    }

    private function mergeIncomingFriendships(): void
    {
        $source_friendships = TrooperFriend::query()
            ->withTrashed()
            ->where(TrooperFriend::FRIEND_ID, $this->source_trooper->id)
            ->where(TrooperFriend::TROOPER_ID, '!=', $this->source_trooper->id)
            ->orderBy(TrooperFriend::ID)
            ->get();

        foreach ($source_friendships as $source_friendship)
        {
            if ($source_friendship->trooper_id === $this->target_trooper->id)
            {
                $source_friendship->forceDelete();

                continue;
            }

            $target_friendship = $this->getTargetInverseFriendship($source_friendship->trooper_id);

            if ($target_friendship === null)
            {
                $source_friendship->friend_id = $this->target_trooper->id;
                $source_friendship->save();

                continue;
            }

            if ($target_friendship->trashed() && !$source_friendship->trashed())
            {
                $target_friendship->restore();
            }

            $source_friendship->forceDelete();
        }
    }

    private function getTargetFriendship(int $friend_id): ?TrooperFriend
    {
        return TrooperFriend::query()
            ->withTrashed()
            ->where(TrooperFriend::TROOPER_ID, $this->target_trooper->id)
            ->where(TrooperFriend::FRIEND_ID, $friend_id)
            ->first();
    }

    private function getTargetInverseFriendship(int $trooper_id): ?TrooperFriend
    {
        return TrooperFriend::query()
            ->withTrashed()
            ->where(TrooperFriend::TROOPER_ID, $trooper_id)
            ->where(TrooperFriend::FRIEND_ID, $this->target_trooper->id)
            ->first();
    }
}
