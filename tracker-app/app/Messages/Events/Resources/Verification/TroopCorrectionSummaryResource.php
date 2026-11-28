<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Verification;

use App\Models\TroopCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What a trooper who never signed up said to prove they were at the troop, on a row of the
 * Troop Verifications queue.
 *
 * @mixin TroopCorrection
 */
class TroopCorrectionSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            TroopCorrection::DETAILS => $this->details,
            'coordinator_denied' => $this->coordinator_denied_at !== null,
        ];
    }
}
