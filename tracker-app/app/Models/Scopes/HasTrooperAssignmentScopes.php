<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Models\TrooperAssignment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the TrooperAssignment model.
 *
 * This trait provides query scopes for filtering and retrieving trooper-organization
 * assignments, including member and moderator relationships.
 */
trait HasTrooperAssignmentScopes
{
}