<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Costumes;

use App\Models\Costume;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeCostumesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->has_developer_access;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_costume_id' => [
                'required',
                'integer',
                Rule::exists(Costume::class, Costume::ID)
                    ->whereNull(Costume::DELETED_AT)
                    ->whereNotIn(Costume::NAME, [Costume::HANDLER, Costume::COMMAND_STAFF]),
            ],
            'target_costume_id' => [
                'required',
                'integer',
                'different:source_costume_id',
                Rule::exists(Costume::class, Costume::ID)->whereNull(Costume::DELETED_AT),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'source_costume_id.required' => 'Duplicate costume is required.',
            'source_costume_id.exists' => 'The selected duplicate costume does not exist or cannot be merged.',
            'target_costume_id.required' => 'Costume to keep is required.',
            'target_costume_id.different' => 'Costume to keep must be different from the duplicate.',
            'target_costume_id.exists' => 'The selected costume to keep does not exist.',
        ];
    }
}
