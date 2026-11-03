<?php

declare(strict_types=1);

namespace App\Plugins;

use InvalidArgumentException;

/**
 * One admin-editable setting declared in a plugin's plugin.json.
 */
final readonly class PluginSetting
{
    public const TYPES = ['text', 'url', 'email', 'number', 'boolean', 'secret'];

    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public mixed $default,
        public string $help,
    ) {}

    public static function fromArray(array $data): self
    {
        $key = (string) ($data['key'] ?? '');
        $type = (string) ($data['type'] ?? 'text');

        if (preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1)
        {
            throw new InvalidArgumentException("Plugin setting key [{$key}] must be snake_case.");
        }

        if (!in_array($type, self::TYPES, true))
        {
            throw new InvalidArgumentException("Plugin setting [{$key}] has unknown type [{$type}].");
        }

        return new self(
            key: $key,
            label: (string) ($data['label'] ?? $key),
            type: $type,
            default: $data['default'] ?? null,
            help: (string) ($data['help'] ?? ''),
        );
    }

    /**
     * Laravel validation rules for this setting's submitted value.
     *
     * @return array<int, string>
     */
    public function rules(): array
    {
        return match ($this->type)
        {
            'url' => ['nullable', 'url', 'max:2048'],
            'email' => ['nullable', 'email', 'max:255'],
            'number' => ['nullable', 'numeric'],
            'boolean' => ['nullable', 'boolean'],
            default => ['nullable', 'string', 'max:2048'],
        };
    }

    public function cast(mixed $value): mixed
    {
        if ($value === null || $value === '')
        {
            return $this->type === 'boolean' ? false : null;
        }

        return match ($this->type)
        {
            'number' => $value + 0,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => trim((string) $value),
        };
    }
}
