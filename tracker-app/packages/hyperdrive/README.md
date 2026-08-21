# Hyperdrive Message Architecture

Hyperdrive is a small Laravel message-dispatch layer that keeps HTTP concerns out of application logic while making message inputs predictable and strongly typed. The package is centered on `Message` and `MessageDispatcher`, which hydrate constructor arguments from a request, route data, auth context, and explicit overrides, then resolve the message's `handle()` method through Laravel's container.

This project uses Hyperdrive to support single-file messages that behave like commands, queries, and page-data fetchers without requiring a separate handler class for every action.

---

## Core behavior

`MessageDispatcher` performs five important steps:

1. Detects circular message chains using a processing stack.
2. Collects request payloads and route parameters.
3. Adds the authenticated actor as `['actor' => auth()->user()]` when applicable.
4. Resolves constructor arguments by name and type.
5. Executes the message's `handle()` method through Laravel's dependency injection container.

A message is considered invalid if a required constructor parameter is missing or if a value cannot be coerced to the declared type.

---

## Message execution flow

```php
$users = UsersPageData::call($request);
```

Under the hood, `MessageDispatcher::handle()` does the following:

- Uses `$request->validated()` for `FormRequest` payloads.
- Falls back to `$request->query()` for standard `Request` objects.
- Merges `$request->route()->parameters()` into the input set.
- Adds the active auth user under the `actor` key when available.
- Merges explicit `::call([...])` arguments last so they override earlier values.
- Resolves the message instance and calls `handle()`.

The processing stack prevents recursive loops such as `A -> B -> A` by throwing an exception.

---

## Parameter precedence

The merge order is deliberate and matches the current implementation:

```text
request validated payload
  + route parameters
  + auth actor
  + explicit call arguments
```

That means a value supplied through `::call(['id' => 42])` wins over a matching value from the request or route.

---

## Supported coercions

`MessageDispatcher` resolves constructor parameters via reflection and supports the following behavior in this implementation:

- `int` => cast with `(int) $value`
- `float` => cast with `(float) $value`
- `bool` => uses `filter_var($value, FILTER_VALIDATE_BOOLEAN)`
- `string` => trims and casts to string
- Eloquent model type => if the value is already an instance, it is used directly; otherwise it calls `Model::findOrFail($value)`
- Backed enum types => resolve via `EnumType::tryFrom($value)`
- Unit enum types => match by case name against the string value
- Nullable unions such as `string|null` or `FooEnum|null` resolve to the non-null type before applying the coercion rules
- Enum unions such as `StatusA|StatusB` are attempted in order until one matches

If any required argument is missing or invalid, `MessageDispatcher` throws an `InvalidArgumentException` with a structured validation payload.

---

## Actor injection

The dispatcher supports auto-binding the authenticated actor when a constructor parameter is type-hinted as `Hyperdrive\Contracts\Actor`.

```php
public function __construct(
    public readonly Actor $actor,
)
```

This is not a generic user lookup; it is a direct match against the interface type and uses the active auth user.

---

## Required constructor rules

The dispatcher checks each constructor parameter in order:

- If a parameter name exists in the merged input array, it is used.
- If the parameter is `actor`, it uses the auth user automatically.
- If the value is missing and the parameter is optional, it is skipped.
- If the value is missing and the parameter is required, an `InvalidArgumentException` is thrown.
- If the value is present but null for a non-nullable parameter, it is also rejected.

This keeps validation at the transport layer lightweight while preserving message-level validation for business rules.

---

## Example message

```php
namespace App\Messages\Troopers;

use Hyperdrive\Message;
use Hyperdrive\Contracts\Actor;
use App\Models\Trooper;

/**
 * @method static Trooper call(...$args)
 */
final class GetTrooperProfile extends Message
{
    public function __construct(
        public readonly int $trooper_id,
        public readonly Actor $actor,
    ) {}

    public function handle(): Trooper
    {
        return Trooper::findOrFail($this->trooper_id);
    }
}
```

This message can be called directly from a controller or nested from another message:

```php
$trooper = GetTrooperProfile::call($request);
```

When the request contains `trooper_id`, route data, or auth context, the dispatcher hydrates that constructor automatically.

---

## Important implementation notes

- Form requests are treated as safe sources of validated data; `validated()` is used instead of raw request input.
- Standard requests use `query()` as the request payload source.
- The dispatcher is intentionally strict: invalid input causes exceptions rather than silently coercing to a misleading value.
- Message recursion is guarded to prevent accidental circular dispatches.
- `handle()` remains the execution hook; message classes are expected to implement their own domain logic there.

This README reflects the actual behavior of the current `MessageDispatcher` implementation in this repository and may differ from more permissive or idealized documentation in other frameworks or examples.