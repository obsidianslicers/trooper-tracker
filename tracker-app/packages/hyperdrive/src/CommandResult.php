<?php

declare(strict_types=1);

namespace Hyperdrive;

use JsonSerializable;
use Illuminate\Contracts\Support\Arrayable;

/**
 * Summary of CommandResult - allows for overloaded success and failure results with dynamic data access.
 */
class CommandResult implements Arrayable, JsonSerializable
{
    /**
     * Private constructor prevents direct instantiation (`new CommandResult()`).
     */
    private function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly ?string $message = null,
        public readonly array $errors = []
    ) {
    }

    /**
     * Create a successful result.
     */
    public static function success(mixed $data = null, ?string $message = null): static
    {
        return new static(
            success: true,
            data: $data,
            message: $message
        );
    }

    /**
     * Create a failed result.
     */
    public static function failure(?string $message = null, array $errors = [], mixed $data = null): static
    {
        return new static(
            success: false,
            data: $data,
            message: $message,
            errors: $errors
        );
    }

    /**
     * Dynamic getter: Look into $data if it's an array or object.
     */
    public function __get(string $name): mixed
    {
        if (is_array($this->data))
        {
            return $this->data[$name] ?? null;
        }

        if (is_object($this->data))
        {
            return $this->data->{$name} ?? null;
        }

        return null;
    }

    public function __isset(string $name): bool
    {
        if (is_array($this->data))
        {
            return isset($this->data[$name]);
        }

        if (is_object($this->data))
        {
            return isset($this->data->{$name});
        }

        return false;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'errors' => $this->errors,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}