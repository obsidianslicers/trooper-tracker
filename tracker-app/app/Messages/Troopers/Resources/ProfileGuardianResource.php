<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The Guardian form on a trooper's Command Staff profile (admin/troopers/components/profile/
 * ProfileGuardian), for a trooper whose club needs a guardian for minors.
 *
 * @mixin Trooper
 */
class ProfileGuardianResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::DATE_OF_BIRTH => $this->date_of_birth?->format('Y-m-d'),
            'guardian_email' => $this->guardian?->email,
        ];
    }
}
