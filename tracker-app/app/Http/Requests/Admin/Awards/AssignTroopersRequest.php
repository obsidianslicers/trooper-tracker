<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Awards;

use App\Models\Trooper;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for assigning an award to troopers.
 *
 * This class defines validation rules for assigning awards to troopers,
 * ensuring that the selected troopers exist and can be assigned the award.
 */
class AssignTroopersRequest extends FormRequest
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
     * Validates the trooper IDs and award date.
     *
     * @return array<string, mixed> The validation rules for assigning an award.
     */
    public function rules(): array
    {
        return [
            'trooper_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'trooper_ids.*' => [
                'integer',
                'exists:'.Trooper::class.','.Trooper::ID,
            ],
            'award_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }
}
