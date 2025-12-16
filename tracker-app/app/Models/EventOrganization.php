<?php

namespace App\Models;

use App\Models\Base\EventOrganization as BaseEventOrganization;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents the relationship between an event and an organization.
 *
 * This model tracks which organizations are associated with an event and whether
 * members of that organization are allowed to attend. It enables fine-grained
 * control over event participation by organization membership.
 */
class EventOrganization extends BaseEventOrganization
{
    use HasFactory;
    use HasTrooperStamps;
}
