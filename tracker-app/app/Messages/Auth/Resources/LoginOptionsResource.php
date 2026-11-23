<?php

declare(strict_types=1);

namespace App\Messages\Auth\Resources;

use Illuminate\Http\Request;

/**
 * The login page's choices (auth/Login): the sign-up page's providers, plus whether the
 * email/password form is on.
 *
 * @property array{providers: array<int, array{key: string, name: string, icon_url: string}>, required: array{key: string, name: string}|null, email_password: array{enabled: bool}} $resource
 */
class LoginOptionsResource extends SignUpOptionsResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'email_password' => [
                'enabled' => $this->resource['email_password']['enabled'],
            ],
        ];
    }
}
