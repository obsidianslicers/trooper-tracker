<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Costumes;

use App\Models\Costume;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request class for validating costume updates.
 *
 * Defines validation rules for costume updates, ensuring the costume name
 * remains unique globally while excluding the current costume from the check.
 */
class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Verifies that the costume exists in the route and that the authenticated
     * user has permission to update it.
     *
     * @return bool True if the user is authorized to update the costume.
     *
     * @throws AuthorizationException If the costume is not found in the route.
     */
    public function authorize(): bool
    {
        $costume = $this->route('costume');

        if ($costume === null)
        {
            throw new AuthorizationException('Costume not found or unauthorized.');
        }

        return $this->user()->can('update', $costume);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Validates the costume name ensuring it remains unique, excluding the
     * current costume from the uniqueness check, and validates selected organizations
     * from the checkbox form.
     *
     * @return array<string, mixed> The validation rules for updating a costume.
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:128',
                Rule::unique(Costume::class, Costume::NAME)
                    ->ignore($this->route('costume')),
            ],
            'organizations' => ['nullable', 'array'],
            'organizations.*.selected' => ['nullable', 'in:1'],
        ];

        return $rules;
    }

    /**
     * Extract selected organization IDs from the form submission.
     *
     * The form submits selected organizations as organizations[id][selected]=1
     * for each checked checkbox. This method extracts the organization IDs
     * from the validated input.
     *
     * @return array<int> Array of selected organization IDs
     */
    public function selected_organization_ids(): array
    {
        $selected = [];
        foreach ($this->input('organizations', []) as $org_id => $data)
        {
            if (!empty($data['selected']))
            {
                $selected[] = (int) $org_id;
            }
        }

        return $selected;
    }
}
