<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Models\TrooperOrganization;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait containing local scopes for the TrooperOrganization model.
 *
 * This trait provides query scopes for filtering and retrieving trooper-organization
 * pivot relationships, including membership status and verification states.
 */
trait HasTrooperOrganizationScopes
{
}