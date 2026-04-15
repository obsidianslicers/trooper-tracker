<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Awards;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for removing an award from a trooper.
 */
class RemoveTrooperRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Returns true if the user has permission to update the award.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('award'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'remove_trooper_id' => ['required', 'integer'],
        ];
    }
}
