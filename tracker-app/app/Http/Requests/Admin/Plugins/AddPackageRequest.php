<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plugins;

use App\Rules\Admin\Plugins\VersionConstraintRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a Composer-managed plugin to add, such as "acme/trooptracker-badges".
 */
class AddPackageRequest extends FormRequest
{
    public const NAME_PATTERN = '/^[a-z0-9]([_.-]?[a-z0-9]+)*'
        .'\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$/';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', 'regex:'.self::NAME_PATTERN],
            'version_constraint' => ['nullable', 'string', 'max:64', new VersionConstraintRule],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Use the Composer package name, such as acme/trooptracker-badges.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => strtolower(trim((string) $this->input('name')))]);
    }
}
