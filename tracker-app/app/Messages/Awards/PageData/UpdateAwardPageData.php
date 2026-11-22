<?php

declare(strict_types=1);

namespace App\Messages\Awards\PageData;

use App\Messages\Awards\Resources\AwardResource;
use App\Models\Award;
use Hyperdrive\Message;

/**
 * @method static array call(Award $award)
 */
final class UpdateAwardPageData extends Message
{
    public function __construct(private readonly Award $award) {}

    public function handle(): array
    {
        $this->award->loadMissing('organization');

        return [
            'award' => new AwardResource($this->award),
        ];
    }
}
