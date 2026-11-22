<?php

declare(strict_types=1);

namespace App\Messages\Awards\Commands;

use App\Enums\AwardFrequency;
use App\Models\Award;
use Hyperdrive\Message;

/**
 * @method static Award call(int $organization_id, string $name, AwardFrequency $frequency)
 */
final class CreateAward extends Message
{
    public function __construct(
        private readonly int $organization_id,
        private readonly string $name,
        private readonly AwardFrequency $frequency,
    ) {}

    public function handle(): Award
    {
        $award = new Award;

        $award->organization_id = $this->organization_id;
        $award->name = $this->name;
        $award->frequency = $this->frequency;

        $award->save();

        return $award;
    }
}
