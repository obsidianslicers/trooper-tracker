<?php

declare(strict_types=1);

namespace App\Rules\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Only an administrator may move a trooper into or out of a status that locks them out
 * (Invalid, Departed, Merged). Moderators can still save a profile that already has one.
 */
class AdminOnlyStatusRule implements ValidationRule
{
    /**
     * @param  Trooper|null  $actor  The trooper making the change.
     * @param  Trooper|null  $subject  The trooper being changed.
     */
    public function __construct(
        private readonly ?Trooper $actor,
        private readonly ?Trooper $subject,
    ) {}

    /**
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $status = MembershipStatus::tryFrom((string) $value);
        $current = $this->subject?->membership_status;

        if ($status === null || $status === $current || $this->actor?->is_administrator)
        {
            return;
        }

        if ($status->isAdminOnly() || $current?->isAdminOnly())
        {
            $fail('Only an administrator can change a trooper to or from this status.');
        }
    }
}
