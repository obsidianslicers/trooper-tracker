<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;

/**
 * @implements CommandHandlerInterface<VoidTrooperCommand>
 */
readonly class VoidTrooperCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  VoidTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $message->trooper->forceFill([
            'membership_status' => MembershipStatus::INVALID,
        ])->save();

        return null;
    }
}
