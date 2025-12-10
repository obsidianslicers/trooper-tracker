<?php

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\TrooperTheme;
use App\Models\Base\Trooper as BaseTrooper;
use App\Models\Casts\LowerCast;
use App\Models\Concerns\HasFilter;
use App\Models\Concerns\HasObserver;
use App\Models\Scopes\HasTrooperScopes;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;

/**
 * Represents a user of the application, typically a member of a costuming organization.
 * This model handles authentication, authorization, and user-specific data and relationships.
 */
class Trooper extends BaseTrooper implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;

    use HasFilter;
    use HasFactory;
    use Notifiable;
    use HasTrooperScopes;
    use HasObserver;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts()
    {
        return array_merge($this->casts, [
            self::MEMBERSHIP_STATUS => MembershipStatus::class,
            self::MEMBERSHIP_ROLE => MembershipRole::class,
            self::EMAIL => LowerCast::class,
            self::THEME => TrooperTheme::class
        ]);
    }

    /**
     * Check if the trooper's membership role is admin.
     *
     * @return bool True if the trooper is admin, false otherwise.
     */
    public function isAdministrator(): bool
    {
        return $this->isActive() && $this->membership_role == MembershipRole::ADMINISTRATOR;
    }

    /**
     * Check if the trooper's membership role is moderator.
     *
     * @return bool True if the trooper is moderator, false otherwise.
     */
    public function isModerator(): bool
    {
        return $this->isActive() && $this->membership_role == MembershipRole::MODERATOR;
    }

    /**
     * Check if the trooper's membership role is handler.
     *
     * @return bool True if the trooper is handler, false otherwise.
     */
    public function isHandler(): bool
    {
        return $this->isActive() && $this->membership_role == MembershipRole::HANDLER;
    }

    /**
     * Check if the trooper's membership status is active.
     *
     * @return bool True if the trooper is active, false otherwise.
     */
    public function isActive(): bool
    {
        return $this->membership_status == MembershipStatus::ACTIVE;
    }

    /**
     * Check if the trooper's membership status is denied.
     *
     * @return bool True if the trooper is denied, false otherwise.
     */
    public function isDenied(): bool
    {
        return $this->membership_status == MembershipStatus::DENIED;
    }

    /**
     * Attach a costume to the trooper if it's not already attached.
     *
     * @param int $costume_id The ID of the costume to attach.
     */
    public function attachCostume(int $costume_id): void
    {
        $trooper_costume = $this->trooper_costumes()->withTrashed()
            ->where(TrooperCostume::COSTUME_ID, $costume_id)
            ->first();

        if ($trooper_costume == null)
        {
            $this->trooper_costumes()->create([TrooperCostume::COSTUME_ID => $costume_id]);
        }
        else
        {
            $trooper_costume->restore();
        }
    }

    /**
     * Detach a costume from the trooper.
     *
     * @param int $costume_id The ID of the costume to detach.
     */
    public function detachCostume(int $costume_id): void
    {
        $this->trooper_costumes()
            ->where(TrooperCostume::COSTUME_ID, $costume_id)
            ->delete();
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
}