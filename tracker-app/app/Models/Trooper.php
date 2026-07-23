<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AchievementType;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Base\Trooper as BaseTrooper;
use App\Models\Casts\LowerCast;
use App\Models\Concerns\HasAuditTrail;
use App\Models\Concerns\HasFilter;
use App\Models\Scopes\HasTrooperScopes;
use Hyperdrive\Contracts\Actor;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Represents a member (trooper) of a costuming organization.
 *
 * Troopers are authenticated users who can participate in events, manage costumes,
 * belong to organizations, and track their trooping activities. This model handles
 * authentication, authorization, membership status, and all trooper-specific data
 * including profile information, preferences, and relationships to events and organizations.
 */
class Trooper extends BaseTrooper implements
    Actor,
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    MustVerifyEmailContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;
    use HasAuditTrail;
    use HasFactory;
    use HasFilter;
    use HasTrooperScopes;
    use Notifiable;

    /**
     * Define the model attributes should be audited for changes.
     *
     * @return array<int, string> Array of attribute names to audit.
     */
    protected function audits(): array
    {
        return [
            self::MEMBERSHIP_ROLE,
            self::MEMBERSHIP_STATUS,
        ];
    }

    /**
     * Get a human-readable label for the trooper.
     *
     * @return string The label representing the trooper.
     */
    public function getAuditLabel(): string
    {
        return $this->display_name . ' (' . $this->email . ')';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::NOTIFICATION_FREQUENCY => NotificationFrequency::class,
            self::MEMBERSHIP_STATUS => MembershipStatus::class,
            self::MEMBERSHIP_ROLE => MembershipRole::class,
            self::EMAIL => LowerCast::class,
            self::THEME => TrooperTheme::class,
        ]);
    }

    public function event_watches(): HasMany
    {
        return $this->hasMany(EventWatch::class);
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(TrooperNotification::class, 'notifiable')->latest();
    }

    public function unreadNotifications(): MorphMany
    {
        return $this->morphMany(TrooperNotification::class, 'notifiable')->whereNull('read_at');
    }

    /**
     * Alias for trooper
     *
     * @return BelongsTo
     */
    public function guardian(): BelongsTo
    {
        return $this->trooper();
    }

    public function minors(): HasMany
    {
        return $this->troopers();
    }

    /**
     * Check if the trooper's membership role is admin.
     *
     * @return bool True if the trooper is admin, false otherwise.
     */
    public function getIsAdministratorAttribute(): bool
    {
        return $this->is_active && $this->membership_role == MembershipRole::ADMINISTRATOR;
    }

    /**
     * Check if the trooper's membership role is moderator.
     *
     * @return bool True if the trooper is moderator, false otherwise.
     */
    public function getIsModeratorAttribute(): bool
    {
        return $this->is_active && $this->membership_role == MembershipRole::MODERATOR;
    }

    /**
     * Check if the trooper's membership role is handler.
     *
     * @return bool True if the trooper is handler, false otherwise.
     */
    public function getIsHandlerAttribute(): bool
    {
        return $this->is_active && $this->membership_role == MembershipRole::HANDLER;
    }

    /**
     * Check if the trooper's membership role is member.
     *
     * @return bool True if the trooper is member, false otherwise.
     */
    public function getIsMemberAttribute(): bool
    {
        return $this->is_active && $this->membership_role == MembershipRole::MEMBER;
    }

    /**
     * Check if the trooper's membership status is active.
     *
     * @return bool True if the trooper is active, false otherwise.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->membership_status == MembershipStatus::ACTIVE;
    }

    /**
     * Check if the trooper's membership status is denied.
     *
     * @return bool True if the trooper is denied, false otherwise.
     */
    public function getIsDeniedAttribute(): bool
    {
        return $this->membership_status == MembershipStatus::DENIED;
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->membership_status == MembershipStatus::PENDING;
    }

    public function getIsVisitorAttribute(): bool
    {
        return $this->membership_role === MembershipRole::VISITOR;
    }

    public function getVisitorAccessExpiredAttribute(): bool
    {
        return $this->is_visitor
            && $this->visitor_expires_at !== null
            && $this->visitor_expires_at->isPast();
    }

    /**
     * Check if trooper is a minor.
     *
     * @return bool True if the trooper is a minor, false otherwise.
     */
    public function getIsMinorAttribute(): bool
    {
        if ($this->date_of_birth)
        {
            $age = $this->date_of_birth->age;

            return $age < 18;
        }

        return false;
    }

    /**
     * Check if trooper is an adult
     *
     * @return bool True if the trooper is an adult, false otherwise.
     */
    public function getIsAdultAttribute(): bool
    {
        return !$this->is_minor;
    }

    public function getHasGuardianRequiredMembershipAttribute(): bool
    {
        return $this->organizations()
            ->where(Organization::REQUIRES_GUARDIAN, true)
            ->wherePivotNull('deleted_at')
            ->exists();
    }

    /**
     * Check if the trooper has an active status in any of their assigned organizations.
     *
     * @return bool True if at least one active assignment exists, false otherwise.
     */
    public function hasActiveOrganizationStatus(): bool
    {
        $has_assignment = $this->trooper_assignments()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->exists();

        return $has_assignment;
    }

    /**
     * Determine if the trooper is a moderator for the given organization.
     *
     * Administrators are always considered moderators for all organizations.
     * Moderators must have a moderator assignment for the specific organization.
     *
     * @param  Organization  $organization  The organization to check moderator status for
     * @return bool True if trooper is a moderator for the organization
     */
    public function resolveModeratorOrgIds(): ?array
    {
        if ($this->is_administrator)
        {
            return null;
        }

        return $this->trooper_assignments()
            ->where(TrooperAssignment::IS_MODERATOR, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID)
            ->toArray();
    }

    public function isModeratorForOrganization(Organization $organization): bool
    {
        if ($this->is_administrator)
        {
            return true;
        }

        if ($this->is_moderator)
        {
            return $this->trooper_assignments()
                ->join('tt_organizations as org_mod', 'tt_trooper_assignments.organization_id', '=', 'org_mod.id')
                ->where(TrooperAssignment::IS_MODERATOR, true)
                ->whereRaw('? LIKE CONCAT(org_mod.node_path, "%")', [$organization->node_path])
                ->exists();
        }

        return false;
    }

    /**
     * Get the top-level organizations this trooper is eligible to troop for at a given event.
     *
     * Returns organizations that:
     * - The trooper has an is_member assignment for (or a parent of such an assignment)
     * - Are listed as can_attend on the event
     *
     * Returns an empty collection when the event has no can_attend restrictions.
     *
     * @param  Event  $event
     * @return Collection<int, Organization>
     */
    public function eligibleOrgsForEvent(Event $event): Collection
    {
        $can_attend_ids = $event->event_organizations
            ->where(EventOrganization::CAN_ATTEND, true)
            ->pluck(EventOrganization::ORGANIZATION_ID);

        if ($can_attend_ids->isEmpty())
        {
            return collect();
        }

        $assignment_org_ids = $this->trooper_assignments()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->pluck(TrooperAssignment::ORGANIZATION_ID);

        if ($assignment_org_ids->isEmpty())
        {
            return collect();
        }

        // Extract the root org ID from each assigned org's node_path (format: "rootId:childId:…:")
        $root_org_ids = Organization::whereIn('id', $assignment_org_ids)
            ->pluck(Organization::NODE_PATH)
            ->map(fn($path) => (int) Str::before($path, Organization::NODE_PATH_SEP))
            ->filter()
            ->unique()
            ->filter(fn($id) => $can_attend_ids->contains($id));

        return Organization::whereIn('id', $root_org_ids)->get();
    }

    /**
     * Check if the trooper has a valid email address.
     *
     * Validates that the email attribute is set and passes PHP's email validation filter.
     *
     * @return bool True if the trooper has a valid email address, false otherwise.
     */
    public function wantsNotification(string $category, string $channel = 'mail'): bool
    {
        return $this->notification_preferences[$category][$channel] ?? true;
    }

    public function emailAppearsValid(): bool
    {
        if ($this->email && filter_var($this->email, FILTER_VALIDATE_EMAIL))
        {
            return true;
        }

        return false;
    }

    /**
     * Get the trooper achievement for a specific achievement type.
     *
     * @param AchievementType $type The type of achievement to retrieve
     * @return TrooperAchievement|null The trooper achievement if found, or null if not found
     */
    public function getTrooperAchievement(AchievementType $type, ?int $organization_id = null): ?TrooperAchievement
    {
        return $this->trooper_achievements
            ->where(TrooperAchievement::TYPE, $type)
            ->where(TrooperAchievement::ORGANIZATION_ID, $organization_id)
            ->first();
    }

    /**
     * Get the trooper achievement for a specific achievement type.
     *
     * @param AchievementType $type The type of achievement to retrieve
     * @return mixed|null The value of the trooper achievement if found, or null if not found
     */
    public function getAchievementValue(AchievementType $type, ?int $organization_id = null): mixed
    {
        $trooper_achievement = $this->getTrooperAchievement($type, $organization_id);

        if ($trooper_achievement)
        {
            return $trooper_achievement->value;
        }

        return null;
    }

    /**
     * Resolve an Imperial Title based on leaderboard position using powers of two.
     * @return string The Imperial Title.
     */
    public function getTitleByTrooperRank(): string
    {
        $shifts = $this->getAchievementValue(AchievementType::TROOPER_SHIFTS);

        if ($shifts === null || $shifts < 2)
        {
            return match ($this->theme->value)
            {
                TrooperTheme::CLONE ->value => 'Cadet',
                TrooperTheme::BOUNTY_HUNTER->value => 'Foundling',
                TrooperTheme::REBEL->value => 'Outcast',
                TrooperTheme::SITH->value => 'Initiate',
                TrooperTheme::STORMTROOPER->value => 'Recruit',
                default => 'Recruit',
            };
        }

        $rank = (int) $this->getAchievementValue(AchievementType::TROOPER_RANK);

        return match ($this->theme->value)
        {
            TrooperTheme::CLONE ->value => match (true)
                {
                    $rank === 1 => 'Supreme Commander',
                    $rank <= 3 => 'Marshal Commander',
                    $rank <= 7 => 'Senior Commander',
                    $rank <= 15 => 'Regimental Commander',
                    $rank <= 31 => 'Battalion Commander',
                    $rank <= 63 => 'Captain',
                    $rank <= 127 => 'Lieutenant',
                    $rank <= 255 => 'Sergeant Major',
                    $rank <= 511 => 'Sergeant',
                    $rank <= 1023 => 'Corporal',
                    $rank <= 2047 => 'Trooper',
                    default => 'Cadet',
                },
            TrooperTheme::BOUNTY_HUNTER->value => match (true)
                {
                    $rank === 1 => 'Guild Master',
                    $rank <= 3 => 'Apex Predator',
                    $rank <= 7 => 'Legendary Hunter',
                    $rank <= 15 => 'Elite Enforcer',
                    $rank <= 31 => 'Veteran Tracker',
                    $rank <= 63 => 'Guild Contractor',
                    $rank <= 127 => 'Huntsman',
                    $rank <= 255 => 'Tracker',
                    $rank <= 511 => 'Specialist',
                    $rank <= 1023 => 'Hireling',
                    $rank <= 2047 => 'Fledgling',
                    default => 'Foundling',
                },
            TrooperTheme::REBEL->value => match (true)
                {
                    $rank === 1 => 'Supreme Commander',
                    $rank <= 3 => 'General',
                    $rank <= 7 => 'Commander',
                    $rank <= 15 => 'Captain',
                    $rank <= 31 => 'Major',
                    $rank <= 63 => 'Lieutenant',
                    $rank <= 127 => 'Vanguard',
                    $rank <= 255 => 'Sergeant',
                    $rank <= 511 => 'Corporal',
                    $rank <= 1023 => 'Private',
                    $rank <= 2047 => 'Recruit',
                    default => 'Outcast',
                },
            TrooperTheme::SITH->value => match (true)
                {
                    $rank === 1 => 'Sith Emperor',
                    $rank <= 3 => 'Sith Lord',
                    $rank <= 7 => 'Sith Master',
                    $rank <= 15 => 'Inquisitor',
                    $rank <= 31 => 'Marauder',
                    $rank <= 63 => 'Assassin',
                    $rank <= 127 => 'Overseer',
                    $rank <= 255 => 'Apprentice',
                    $rank <= 511 => 'Acolyte',
                    $rank <= 1023 => 'Neophyte',
                    $rank <= 2047 => 'Adept',
                    default => 'Initiate',
                },
            TrooperTheme::STORMTROOPER->value => match (true)
                {
                    $rank === 1 => 'Grand Moff',
                    $rank <= 3 => 'Moff',
                    $rank <= 7 => 'General',
                    $rank <= 15 => 'Colonel',
                    $rank <= 31 => 'Major',
                    $rank <= 63 => 'Captain',
                    $rank <= 127 => 'Lieutenant',
                    $rank <= 255 => 'Sergeant Major',
                    $rank <= 511 => 'Sergeant',
                    $rank <= 1023 => 'Corporal',
                    $rank <= 2047 => 'Trooper',
                    default => 'Recruit',
                },
            default => match (true)
                {
                    $rank === 1 => 'Grand Moff',
                    $rank <= 3 => 'Moff',
                    $rank <= 7 => 'General',
                    $rank <= 15 => 'Colonel',
                    $rank <= 31 => 'Major',
                    $rank <= 63 => 'Captain',
                    $rank <= 127 => 'Lieutenant',
                    $rank <= 255 => 'Sergeant Major',
                    $rank <= 511 => 'Sergeant',
                    $rank <= 1023 => 'Corporal',
                    $rank <= 2047 => 'Trooper',
                    default => 'Recruit',
                },
        };
    }

    /**
     * Get the theme color based on rank position.
     *
     * @return string The theme color key used by the UI.
     */
    public function getRankTheme(): string
    {
        $rank = (int) $this->getAchievementValue(AchievementType::TROOPER_RANK);

        return match (true)
        {
            $rank <= 4 => 'danger',  // Command Red (Grand Moff to General)
            $rank <= 64 => 'primary', // Operations Blue (Colonel to Captain)
            $rank <= 128 => 'info',    // Tactical Cyan (Lieutenant)
            default => 'secondary', // Standard Issue Grey
        };
    }
}
