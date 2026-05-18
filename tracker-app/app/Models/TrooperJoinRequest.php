<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JoinRequestStatus;
use App\Models\Base\TrooperJoinRequest as BaseTrooperJoinRequest;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasTrooperJoinRequestScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a trooper's request to join a club organization.
 *
 * Tracks pending, approved, and denied requests for club membership.
 * On approval a TrooperAssignment is created and, when provided, the
 * identifier is persisted to TrooperOrganization.
 */
class TrooperJoinRequest extends BaseTrooperJoinRequest
{
    use HasTrooperJoinRequestScopes;
    use HasObserver;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => JoinRequestStatus::class,
        ]);
    }
}
