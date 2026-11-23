<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A trooper found by the trooper search (search.troopers), as the trooper picker lists them:
 * both names and the email, to tell people with the same name apart.
 *
 * @mixin Trooper
 */
class TrooperSearchResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::ID => $this->id,
            Trooper::LEGAL_NAME => $this->legal_name,
            Trooper::DISPLAY_NAME => $this->display_name,
            Trooper::EMAIL => $this->email,
        ];
    }
}
