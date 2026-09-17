<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\ServiceRecords;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignCreditRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event_trooper = $this->route('event_trooper');

        if ($this->user()->is_administrator)
        {
            return true;
        }

        return Trooper::moderatedBy($this->user())
            ->where(Trooper::ID, $event_trooper->trooper_id)
            ->exists();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['integer', Rule::exists(Organization::class, Organization::ID)],
        ];
    }
}
