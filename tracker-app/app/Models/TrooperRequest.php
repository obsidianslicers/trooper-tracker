<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrooperRequestStatus;
use App\Models\Base\TrooperRequest as BaseTrooperRequest;
use App\Models\Concerns\HasObserver;
use App\Models\Scopes\HasTrooperRequestScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrooperRequest extends BaseTrooperRequest
{
    use HasFactory;
    use HasTrooperRequestScopes;
    use HasObserver;

    protected function casts(): array
    {
        return array_merge($this->casts, [
            self::STATUS => TrooperRequestStatus::class,
        ]);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::ORGANIZATION_ID);
    }

    //  TODO remove for convention casing reasons
    public function primaryOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::PRIMARY_ORGANIZATION_ID);
    }

    public function primary_organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, self::PRIMARY_ORGANIZATION_ID);
    }
}
