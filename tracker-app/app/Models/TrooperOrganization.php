<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Models\Base\TrooperOrganization as BaseTrooperOrganization;
use App\Models\Concerns\HasObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a trooper's membership relationship with an external organization.
 *
 * This model tracks memberships in organizations outside the primary system
 * hierarchy, including membership numbers, status, and dates. Examples include
 * tracking 501st Legion membership numbers or other costuming group affiliations.
 */
class TrooperOrganization extends BaseTrooperOrganization
{
    use HasFactory;
    use HasObserver;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::MEMBERSHIP_STATUS => MembershipStatus::class,
        ]);
    }
}
