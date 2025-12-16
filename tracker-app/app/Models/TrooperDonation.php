<?php

namespace App\Models;

use App\Models\Base\TrooperDonation as BaseTrooperDonation;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasTrooperDonationScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a charitable donation made by a trooper.
 *
 * This model tracks donations made by troopers to charitable causes,
 * either directly or in association with events. It helps track the
 * charitable impact of the organization and individual contributions.
 */
class TrooperDonation extends BaseTrooperDonation
{
    use HasTrooperDonationScopes;
    use HasFactory;
    use HasTrooperStamps;
}
