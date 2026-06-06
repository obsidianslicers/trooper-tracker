<?php

declare(strict_types=1);

namespace App\Messages;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use UnitEnum;

/**
 * Base transport message used by the message bus.
 *
 * Messages are hydrated from raw request payloads before they enter
 * validation, authorization, and handler execution pipelines.
 */
abstract readonly class Message
{
    /**
     * Hydrate the message from transport payload data.
     *
     * This performs lightweight transport coercion and required-parameter checks so
     * semantic validation remains in message-level validation rules.
     *
     * @param  Request  $request
     * @return static
     *
     * @throws ValidationException If a required constructor argument is omitted or null.
     */
    public static function fromRequest(Request $request): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null)
        {
            return new static;
        }

        $resolved_params = [];
        $validation_errors = [];

        foreach ($constructor->getParameters() as $parameter)
        {
            $name = $parameter->getName();
            $has_input = $request->has($name);
            $value = $has_input ? $request->input($name) : null;

            if (!$has_input && $parameter->isOptional())
            {
                continue;
            }

            if (!$has_input && !$parameter->isOptional() && !$parameter->allowsNull())
            {
                $validation_errors[$name] = ["The {$name} parameter is required."];
                continue;
            }

            if ($value === null && !$parameter->allowsNull())
            {
                $validation_errors[$name] = ["The {$name} parameter is required."];
                continue;
            }

            try
            {
                $resolved_params[$name] = self::resolveParameterValue($parameter, $value);
            }
            catch (ValidationException $e)
            {
                $validation_errors = array_merge($validation_errors, $e->errors());
            }
            catch (ModelNotFoundException)
            {
                $validation_errors[$name] = ["The selected {$name} is invalid or does not exist."];
            }
        }

        if ($validation_errors !== [])
        {
            throw ValidationException::withMessages($validation_errors);
        }

        return $reflection->newInstanceArgs($resolved_params);
    }

    /**
     * Resolve and coerce a constructor parameter value from payload input.
     *
     * @param  ReflectionParameter  $parameter
     * @param  mixed  $value
     * @return mixed
     * * @throws ValidationException|ModelNotFoundException
     */
    private static function resolveParameterValue(ReflectionParameter $parameter, mixed $value): mixed
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType)
        {
            return $value;
        }

        if ($value === null)
        {
            return null;
        }

        $type_name = $type->getName();

        if (!$type->isBuiltin() && enum_exists($type_name))
        {
            return self::resolveEnum($parameter, $type_name, $value);
        }

        if (!$type->isBuiltin() && is_subclass_of($type_name, Model::class))
        {
            return self::resolveModel($type_name, $value);
        }

        return match ($type_name)
        {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => trim((string) $value),
            default => $value,
        };
    }

    private static function resolveModel(string $type_name, mixed $value): Model
    {
        if ($value instanceof $type_name)
        {
            return $value;
        }

        return $type_name::findOrFail($value);
    }

    private static function resolveEnum(ReflectionParameter $parameter, string $type_name, mixed $value): BackedEnum|UnitEnum
    {
        if ($value instanceof $type_name)
        {
            return $value;
        }

        if (is_subclass_of($type_name, BackedEnum::class))
        {
            $enum = $type_name::tryFrom($value);

            if ($enum !== null)
            {
                return $enum;
            }
        }
        elseif (is_subclass_of($type_name, UnitEnum::class) && is_string($value))
        {
            foreach ($type_name::cases() as $case)
            {
                if ($case->name === $value)
                {
                    return $case;
                }
            }
        }

        throw ValidationException::withMessages([
            $parameter->getName() => ["The {$parameter->getName()} parameter is invalid."],
        ]);
    }
}