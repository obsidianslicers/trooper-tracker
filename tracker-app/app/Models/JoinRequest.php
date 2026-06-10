<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\JoinRequestStatus;
use App\Models\Base\JoinRequest as BaseJoinRequest;
use App\Models\Concerns\HasObserver;
use App\Models\Scopes\HasJoinRequestScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JoinRequest extends BaseJoinRequest
{
    use HasFactory;
    use HasJoinRequestScopes;
    use HasObserver;

    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => JoinRequestStatus::class,
        ]);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::ORGANIZATION_ID);
    }

    public function primaryOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::PRIMARY_ORGANIZATION_ID);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::APPROVED_BY_ID);
    }

    public function deniedBy(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::DENIED_BY_ID);
    }
}
