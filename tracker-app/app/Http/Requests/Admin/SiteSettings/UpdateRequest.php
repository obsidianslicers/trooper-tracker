<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\SiteSettings;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && in_array($user->membership_role, [
            MembershipRole::MODERATOR,
            MembershipRole::ADMINISTRATOR,
        ], true);
    }

    public function rules(): array
    {
        return [
            'site_closed' => ['nullable', 'boolean'],
            'site_message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
