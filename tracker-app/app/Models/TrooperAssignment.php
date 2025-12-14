<?php

namespace App\Models;

use App\Models\Base\TrooperAssignment as BaseTrooperAssignment;
use App\Models\Concerns\HasObserver;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasTrooperAssignmentScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrooperAssignment extends BaseTrooperAssignment
{
    use HasTrooperAssignmentScopes;
    use HasObserver;
    use HasFactory;
    use HasTrooperStamps;
}
