<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\Resources\CommandStaffMemberResource;
use App\Messages\Troopers\Queries\GetCommandStaff;
use Hyperdrive\Message;

/**
 * The troopers on command staff.
 *
 * @method static array<string, mixed> call()
 */
final class CommandStaffPageData extends Message
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'troopers' => CommandStaffMemberResource::collection(GetCommandStaff::call()),
        ];
    }
}
