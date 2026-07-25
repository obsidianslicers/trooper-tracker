<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeTroopersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        $trooper = $this->route('trooper');

        if ($trooper === null)
        {
            throw new AuthorizationException('Trooper not found or unauthorized.');
        }

        return $this->user()->is_administrator;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_trooper_id' => [
                'required',
                Rule::exists(Trooper::class, Trooper::ID)
                    ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE),
            ],
            'target_trooper_id' => [
                'required',
                'different:source_trooper_id',
                Rule::exists(Trooper::class, Trooper::ID)
                    ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_trooper_id.required' => 'Source trooper is required.',
            'source_trooper_id.exists' => 'The selected source trooper does not exist (or is not active).',
            'target_trooper_id.required' => 'Target trooper is required.',
            'target_trooper_id.different' => 'Target trooper must be different from the source trooper.',
            'target_trooper_id.exists' => 'The selected target trooper does not exist (or is not active).',
        ];
    }
}
