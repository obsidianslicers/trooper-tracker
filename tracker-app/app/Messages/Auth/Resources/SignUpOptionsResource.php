<?php

declare(strict_types=1);

namespace App\Messages\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The login providers on the sign-up page (auth/SignUp), from GetAuthConfig: the buttons to show,
 * and the one provider that is required, if any.
 *
 * @property array{providers: array<int, array{key: string, name: string, icon_url: string}>, required: array{key: string, name: string}|null} $resource
 */
class SignUpOptionsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'providers' => LoginProviderResource::collection($this->resource['providers']),
            'required' => $this->resource['required'] === null ? null : [
                'name' => $this->resource['required']['name'],
            ],
        ];
    }
}
