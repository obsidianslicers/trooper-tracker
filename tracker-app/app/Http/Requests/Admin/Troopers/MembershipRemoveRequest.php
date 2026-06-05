<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

class MembershipRemoveRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        $organization = $this->route('organization');

        return $this->user()->can('update', $trooper)
            && Organization::moderatedBy($this->user())
                ->where(Organization::ID, $organization->id)
                ->exists();
    }

    public function rules(): array
    {
        return [];
    }
}
