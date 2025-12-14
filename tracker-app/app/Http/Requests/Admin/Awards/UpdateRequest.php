<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Awards;

use App\Enums\AwardType;
use App\Models\Award;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handles the validation for updating an existing award.
 *
 * This class defines validation rules for updating award information.
 * Only the award name can be modified during an update. The organization
 * and frequency are immutable after creation.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the award exists in the route and that the authenticated
     * user has permission to update it.
     *
     * @return bool Returns true if the user can update the award.
     * @throws AuthorizationException if the award is not found in the route.
     */
    public function authorize(): bool
    {
        $award = $this->route('award');

        if ($award == null)
        {
            throw new AuthorizationException('Award not found or unauthorized.');
        }

        return $this->user()->can('update', $award);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Only validates the award name as other fields are immutable.
     *
     * @return array<string, mixed> The validation rules for updating an award.
     */
    public function rules(): array
    {
        $rules = [
            Award::NAME => [
                'required',
                'string',
                'max:128',
            ],
        ];

        return $rules;
    }
}