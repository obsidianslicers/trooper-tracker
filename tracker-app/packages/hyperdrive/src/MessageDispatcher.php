<?php

namespace Hyperdrive;

use Hyperdrive\Contracts\Actor;
use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use BackedEnum;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use ReflectionParameter;
use UnitEnum;

final class MessageDispatcher
{
    private array $processing = [];

    /**
     * Hydrate a message from the request and execute its corresponding handler.
     */
    public function handle(string $message_class, ?Request $request = null, array $params = []): mixed
    {
        if (in_array($message_class, $this->processing, true))
        {
            $cycle = implode(' -> ', $this->processing) . ' -> ' . $message_class;

            throw new Exception("Circular message call detected: [{$cycle}]");
        }

        $this->processing[] = $message_class;

        try
        {
            $request_params = [];
            $route_params = [];

            if ($request !== null)
            {
                if ($request instanceof FormRequest)
                {
                    // Safe to call ->validated() here!
                    $request_params = $request->validated();
                }
                else
                {
                    // Fallback if it's a standard Request
                    $request_params = $request->query() ?? [];
                }

                $route_params = $request->route() ? $request->route()->parameters() : [];
            }

            $auth_params = ['actor' => auth()->user()];
            $all_params = [
                ...$request_params,
                ...$route_params,
                ...$auth_params,
                ...$params
            ];

            /** @var Message $message */
            $message = $this->resolveMessage($message_class, $all_params);

            if (!method_exists($message, 'handle'))
            {
                throw new Exception("Message class `{$message_class}` does not have a `handle()` method.");
            }

            return app()->call([$message, 'handle']);
        }
        catch (ValidationException $e)
        {
            throw new InvalidArgumentException("Invalid message parameters: " . json_encode($e->errors()));
        }
        catch (Exception $e)
        {
            throw $e;
        }
        finally
        {
            array_pop($this->processing);
        }
    }

    /**
     * Hydrate the message from transport payload data.
     *
     * This performs lightweight transport coercion and required-parameter checks so
     * semantic validation remains in message-level validation rules.
     *
     * @param  array $params Optional additional parameters to use for hydration, such as route parameters.
     * @return static
     *
     * @throws ValidationException If a required constructor argument is omitted or null.
     */
    private function resolveMessage(string $message_class, array $params = []): Message
    {
        $base_name = class_basename($message_class);
        $reflection = new ReflectionClass($message_class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null)
        {
            return new $message_class;
        }

        $resolved_params = [];
        $parameter_errors = [];

        foreach ($constructor->getParameters() as $parameter)
        {
            $name = $parameter->getName();
            $has_input = array_key_exists($name, $params);
            $value = null;

            if (self::isParameterActor($parameter))
            {
                $value = $params['actor'] ?? null;
                $has_input = $value !== null;
            }
            else if ($has_input)
            {
                $value = $params[$name];
            }

            if (!$has_input)
            {
                if ($parameter->isOptional())
                {
                    continue;
                }

                if (!$parameter->isOptional() && !$parameter->allowsNull())
                {
                    $parameter_errors[$name] = ["The Hyperdrive input-parameter `{$base_name}:{$name}` is required."];
                    continue;
                }
            }

            if ($value === null && !$parameter->allowsNull())
            {
                $parameter_errors[$name] = ["The Hyperdrive parameter `{$base_name}:{$name}` is required."];
                continue;
            }

            try
            {
                $resolved_params[$name] = self::resolveParameterValue($parameter, $value);
            }
            catch (ValidationException $e)
            {
                $parameter_errors = array_merge($parameter_errors, $e->errors());
            }
            catch (ModelNotFoundException)
            {
                $parameter_errors[$name] = ["The selected model `{$base_name}:{$name}` is invalid or does not exist."];
            }
        }

        if ($parameter_errors !== [])
        {
            throw new InvalidArgumentException("Invalid message parameters: " . json_encode($parameter_errors));
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
    private function resolveParameterValue(ReflectionParameter $parameter, mixed $value): mixed
    {
        if ($value === null)
        {
            return null;
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType)
        {
            return $this->resolveNamedTypeValue($parameter, $type, $value);
        }

        if ($type instanceof ReflectionUnionType)
        {
            //  only supporting enums right now
            //return $this->resolveNamedTypeValue($parameter, $type, $value);
            $non_null_types = array_filter(
                $type->getTypes(),
                fn(ReflectionNamedType $t) => $t->getName() !== 'null'
            );

            // If it's a simple nullable union like string|null, extract the primary type
            if (count($non_null_types) === 1)
            {
                $primary_type = reset($non_null_types);

                if ($primary_type instanceof ReflectionNamedType)
                {
                    return $this->resolveNamedTypeValue($parameter, $primary_type, $value);
                }
            }

            // 2. Validate that EVERY non-null type in this union is an Enum
            $all_enums = array_reduce(
                $non_null_types,
                fn(bool $carry, ReflectionNamedType $t) => $carry && enum_exists($t->getName()),
                true
            );

            if ($all_enums)
            {
                return $this->resolveEnumUnion($parameter, $non_null_types, $value);
            }
        }

        return $value;
    }

    private function resolveEnumUnion(ReflectionParameter $parameter, array $types, mixed $value): BackedEnum|UnitEnum
    {
        foreach ($types as $type)
        {
            $type_name = $type->getName();

            if (enum_exists($type_name))
            {
                try
                {
                    return $this->resolveEnum($parameter, $type_name, $value);
                }
                catch (ValidationException)
                {
                    // Continue to the next type in the union
                }
            }
        }

        throw ValidationException::withMessages([
            $parameter->getName() => ["The `{$parameter->getName()}={$value}` parameter is invalid for all enum types in the union."],
        ]);
    }

    private function resolveNamedTypeValue(ReflectionParameter $parameter, ReflectionNamedType $type, mixed $value): mixed
    {
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

    private function resolveModel(string $type_name, mixed $value): Model
    {
        if ($value instanceof $type_name)
        {
            return $value;
        }

        return $type_name::findOrFail($value);
    }

    private function resolveEnum(ReflectionParameter $parameter, string $type_name, mixed $value): BackedEnum|UnitEnum
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
            $parameter->getName() => ["The `{$parameter->getName()}` parameter is invalid."],
        ]);
    }

    private function isParameterActor(ReflectionParameter $parameter): bool
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin())
        {
            return false;
        }

        // Direct equality check: Is the parameter exact type-hinted as the Interface?
        if ($type->getName() === Actor::class)
        {
            // Optional double-check: verify that Actor::class is indeed an interface
            return interface_exists($type->getName());
        }

        return false;
    }
}
