<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;

/**
 * @implements CommandHandlerInterface<UnvoidTrooperCommand>
 */
readonly class UnvoidTrooperCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  UnvoidTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $message->trooper->forceFill([
            'membership_status' => MembershipStatus::PENDING,
        ])->save();

        return null;
    }
}
