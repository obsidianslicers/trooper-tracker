<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\PermissionRequestStatus;
use App\Models\Base\PermissionRequest as BasePermissionRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionRequest extends BasePermissionRequest
{
    use HasFactory;

    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => PermissionRequestStatus::class,
            self::MEMBERSHIP_ROLE => MembershipRole::class,
        ]);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::REQUESTER_TROOPER_ID);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Trooper::class, self::REVIEWER_TROOPER_ID);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where(self::STATUS, PermissionRequestStatus::PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === PermissionRequestStatus::PENDING;
    }
}
