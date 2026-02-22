<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\OrganizationCostume as BaseOrganizationCostume;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasOrganizationCostumeScopes;
use App\Models\Scopes\HasToOptionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a costume type within an organization.
 *
 * Organization costumes define the approved character costumes that members
 * can wear when participating in events. Each costume belongs to a specific
 * organization and can be assigned to troopers for event participation.
 */
class OrganizationCostume extends BaseOrganizationCostume
{
    use HasToOptionScope;
    use HasOrganizationCostumeScopes;
    use HasFactory;
    use HasTrooperStamps;
}
