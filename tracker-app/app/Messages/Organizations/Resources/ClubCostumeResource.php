<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use App\Models\Costume;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A costume a club offers, on the Command Staff organization costumes tab, with when the
 * club's roster sync last saw it. Expects the organization_costume pivot.
 *
 * @mixin Costume
 */
class ClubCostumeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Costume::ID => $this->id,
            Costume::NAME => $this->name,
            'synchronized_at' => $this->pivot->synchronized_at
                ? Carbon::parse($this->pivot->synchronized_at)->localFormat('M j, Y')
                : null,
        ];
    }
}
