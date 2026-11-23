<?php

declare(strict_types=1);

namespace App\Messages\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the registration page (auth/Register) takes from the sign-up session: the email a login
 * provider gave, which fills in the form.
 *
 * @property array{session: array<string, mixed>} $resource
 */
class RegistrationSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session' => [
                'email' => $this->resource['session']['email'] ?? null,
            ],
        ];
    }
}
