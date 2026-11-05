<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Plugins;

use App\Enums\PackageUpdatePolicy;
use App\Rules\Admin\Plugins\VersionConstraintRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return self::fieldRules();
    }

    /**
     * Shared with the plugins:add-package command.
     *
     * @return array<string, mixed>
     */
    public static function fieldRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:128', 'regex:'.self::NAME_PATTERN],
            'version_constraint' => ['nullable', 'string', 'max:64', new VersionConstraintRule],
            'repository_url' => ['nullable', 'string', 'max:255', 'url:https'],
            'update_policy' => ['nullable', Rule::enum(PackageUpdatePolicy::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Use the Composer package name, such as acme/trooptracker-badges.',
            'repository_url.url' => 'Use an https:// address for the repository.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower(trim((string) $this->input('name'))),
            'repository_url' => trim((string) $this->input('repository_url')) ?: null,
        ]);
    }
}
