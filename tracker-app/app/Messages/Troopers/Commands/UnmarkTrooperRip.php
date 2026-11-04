<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;

/**
 * @method static void call(Trooper $trooper)
 */
final class UnmarkTrooperRip extends Message
{
    use ShouldBeTransactional;

    public function __construct(private readonly Trooper $trooper) {}

    public function handle(): void
    {
        $this->trooper->forceFill([
            'membership_status' => MembershipStatus::PENDING,
        ])->save();
    }
}
