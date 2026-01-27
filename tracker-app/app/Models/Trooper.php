<?php

namespace App\Models;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Models\Base\Trooper as BaseTrooper;
use App\Models\Casts\LowerCast;
use App\Models\Concerns\HasAuditTrail;
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
 * Represents a member (trooper) of a costuming organization.
 *
 * Troopers are authenticated users who can participate in events, manage costumes,
 * belong to organizations, and track their trooping activities. This model handles
 * authentication, authorization, membership status, and all trooper-specific data
 * including profile information, preferences, and relationships to events and organizations.
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
    use HasAuditTrail;

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
        return $this->name . ' (' . $this->email . ')';
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
            self::THEME => TrooperTheme::class
        ]);
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
     * @param Organization $organization The organization to check moderator status for
     * @return bool True if trooper is a moderator for the organization
     */
    public function isModeratorForOrganization(Organization $organization): bool
    {
        if ($this->is_administrator)
        {
            return true;
        }

        if ($this->is_moderator)
        {
            return $this->trooper_assignments()
                ->where(TrooperAssignment::TROOPER_ID, $this->id)
                ->where(TrooperAssignment::ORGANIZATION_ID, $organization->id)
                ->where(TrooperAssignment::IS_MODERATOR, true)
                ->exists();
        }

        return false;
    }

    /**
     * Check if the trooper has a valid email address.
     *
     * Validates that the email attribute is set and passes PHP's email validation filter.
     *
     * @return bool True if the trooper has a valid email address, false otherwise.
     */
    public function emailAppearsValid(): bool
    {
        if ($this->email && filter_var($this->email, FILTER_VALIDATE_EMAIL))
        {
            return true;
        }

        return false;
    }
}