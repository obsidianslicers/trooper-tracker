<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call()
 */
final class GetTrooperMinors extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {
    }

    public function handle(): Collection
    {
        return $this->trooper->troopers()->orderBy(Trooper::DISPLAY_NAME)->get();
    }
}
