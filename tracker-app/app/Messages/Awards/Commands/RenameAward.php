<?php

declare(strict_types=1);

namespace App\Messages\Awards\Commands;

use App\Models\Award;
use Hyperdrive\Message;

/**
 * An award's name is the only thing that changes after it's created.
 *
 * @method static Award call(Award $award, string $name)
 */
final class RenameAward extends Message
{
    public function __construct(
        private readonly Award $award,
        private readonly string $name,
    ) {}

    public function handle(): Award
    {
        $this->award->name = $this->name;
        $this->award->save();

        return $this->award;
    }
}
