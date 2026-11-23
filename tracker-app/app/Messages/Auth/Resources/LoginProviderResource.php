<?php

declare(strict_types=1);

namespace App\Messages\Auth\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One "Login with ..." button on the login and sign-up pages (auth/components/OAuthLink).
 *
 * @property array{key: string, name: string, icon_url: string} $resource
 */
class LoginProviderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->resource['key'],
            'name' => $this->resource['name'],
            'icon_url' => $this->resource['icon_url'],
        ];
    }
}
