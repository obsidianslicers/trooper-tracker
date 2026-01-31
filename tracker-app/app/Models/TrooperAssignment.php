<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\TrooperAssignment as BaseTrooperAssignment;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasTrooperAssignmentScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a trooper's membership or role assignment within an organization.
 *
 * This model tracks which organizations a trooper belongs to and their roles
 * within those organizations (member, moderator, etc.). A trooper can have
 * multiple assignments across different organizations in the hierarchy.
 */
class TrooperAssignment extends BaseTrooperAssignment
{
    use HasTrooperAssignmentScopes;
    use HasObserver;
    use HasFactory;
    use HasTrooperStamps;
}
