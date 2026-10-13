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

    /**
     * Human-readable lines describing what the request changes, for notifications.
     *
     * @return array<int, string>
     */
    public function changeSummary(): array
    {
        $organizations = Organization::query()
            ->whereIn(Organization::ID, [
                ...$this->moderated_organization_ids ?? [],
                ...$this->unmoderated_organization_ids ?? [],
            ])
            ->pluck(Organization::NAME, Organization::ID);

        $names = fn (?array $ids): string => $organizations->only($ids ?? [])->implode(', ');
        $titles = fn (?array $groups): string => collect($groups ?? [])->pluck('title')->implode(', ');

        $lines = [
            'Role' => match ($this->membership_role)
            {
                MembershipRole::MEMBER => 'Regular Trooper',
                MembershipRole::MODERATOR => 'Moderator',
                default => '',
            },
            'Now moderates' => $names($this->moderated_organization_ids),
            'No longer moderates' => $names($this->unmoderated_organization_ids),
            'Forum groups added' => $titles($this->forum_groups_to_add),
            'Forum groups removed' => $titles($this->forum_groups_to_remove),
        ];

        return collect($lines)
            ->filter(fn (string $value): bool => $value !== '')
            ->map(fn (string $value, string $label): string => "{$label}: {$value}")
            ->values()
            ->all();
    }
}
