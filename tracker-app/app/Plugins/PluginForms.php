<?php

declare(strict_types=1);

namespace App\Plugins;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Lets plugins add fields to core forms.
 *
 * A plugin renders its inputs through the form's UI slot, adds validation rules
 * here, and saves its own data in the "saved" callback. Core forms call rules()
 * from their form request and saved() right after they save, inside the same
 * transaction.
 */
class PluginForms
{
    public const ORGANIZATION_UPDATE = 'admin.organizations.update';

    /**
     * The event create and edit forms. Saved through UpdateEvent, which passes
     * the event and the validated input.
     */
    public const EVENT_UPDATE = 'admin.events.update';

    /**
     * @var array<string, array<int, array{rules: array, saved: ?Closure}>>
     */
    private array $extensions = [];

    /**
     * @param  array<string, mixed>  $rules  Validation rules for the plugin's own inputs.
     * @param  Closure(Model, array): void|null  $saved  Gets the saved model and validated input.
     */
    public function add(string $form, array $rules = [], ?Closure $saved = null): void
    {
        $this->extensions[$form][] = compact('rules', 'saved');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(string $form): array
    {
        return array_merge([], ...array_column($this->extensions[$form] ?? [], 'rules'));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function saved(string $form, Model $model, array $validated): void
    {
        foreach ($this->extensions[$form] ?? [] as $extension)
        {
            if ($extension['saved'] !== null)
            {
                ($extension['saved'])($model, $validated);
            }
        }
    }
}
