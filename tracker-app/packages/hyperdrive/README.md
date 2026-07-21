# Hyperdrive Message Architecture

Hyperdrive is a streamlined, single-file architecture pattern for Laravel that merges the benefits of **CQRS (Command Query Responsibility Segregation)**, **Data Transfer Objects (DTOs)**, and **Action Classes** into unified, self-contained **Messages**.

It eliminates boilerplate, ensures strong runtime type coercion, natively resolves controller dependencies, protects against circular loops, and provides flawless IDE type-hinting and autocomplete.

## Architecture: HTTP vs. Application Layer Separation

The Message bus architecture strictly decoupling Controllers from Page Data classes ensures a clean separation between the HTTP layer (handling routing, middleware, and authorization) and the Application layer (handling data orchestration and payload preparation). By offloading data aggregation to a dedicated Page Data message, controllers remain lightweight and single-responsibility, while the data layer can automatically resolve route parameters, safely sanitize model attributes (->only()), and keep frontend payloads minimal. This prevents the "Fat Controller" anti-pattern, guarantees that UI data-fetching logic is highly testable and decoupled from HTTP state, and maintains a predictable, repeatable contract across the entire application ecosystem.

---

## The Core Concept

Traditional CQRS architecture forces you to create separate files for a `Message/Command` (dumb data) and a `Handler` (logic). Hyperdrive unifies them into a single, cohesive file.

```
┌──────────────────────────────────────────────┐
│                  Controller                  │
└──────────────────────┬───────────────────────┘
                       │  UsersPageData::call($request)
                       ▼
┌──────────────────────────────────────────────┐
│                  Hyperdrive                  │
│ 1. Sniffs FormRequest / Request Payload      │
│ 2. Merges Route Parameters & Auth Actor      │
│ 3. Hydrates & type-coerces into constructor  │
└──────────────────────┬───────────────────────┘
                       │
                       ▼
┌──────────────────────────────────────────────┐
│             Single-File Message              │
│  __construct( public Type $data ) { ... }    │
│  handle( Dependency $service )    { ... }    │
└──────────────────────────────────────────────┘

```

---

## Features

* **Single-File Content:** Input properties, hydration guardrails, and business logic live in the exact same class.
* **Automatic Hydration Extraction:** Intelligently intercepts custom `FormRequest` instances to extract only safe `validated()` parameters, falling back seamlessly to `input()` profiles for raw payloads.
* **Smart Parameter Aggregation:** Automatically marries incoming request bodies with URL route parameters and the globally authenticated user scope.
* **Type Coercion Engine:** Implicitly handles primitives (`int`, `bool`, `string`, `float`), auto-resolves primary keys directly to Eloquent Models via `findOrFail()`, and parses Backed/Unit PHP Enums.
* **Perfect IDE Return Type Inference:** Full static autocomplete out-of-the-box using standard PHPDoc annotations.
* **Infinite Nesting Support:** Isolates internal message calls by tracking parameter signatures, allowing explicit array values or manual named arguments to override global state cleanly.
* **Loop Guardrails:** Features a stateful tracking stack that throws an exception if a circular chain of messages is mistakenly introduced (`ClassA -> ClassB -> ClassA`).

---

## Core Component Overview

### The Base Message

Every application action (Command or Query) extends the abstract `Message` base class.

* Use the class-level `@method static ReturnType call(...$args)` docblock so your IDE tracks output accurately.
* Define input parameters in the `__construct()`.
* Typehint infrastructure or domain dependencies in the execution `handle()` method, which is resolved via Laravel's service container.

---

## Example Implementation

### 1. Creating a Service/Action Message

```php
namespace App\Architecture\Messages;

use App\Models\User;
use App\Models\Plan;
use App\Models\Actor;
use App\Services\PaymentGateway;
use Hyperdrive\Message;

/**
 * @method static User call(...$args)
 */
final class RegisterUserAndSubscribe extends Message
{
    // 1. Data Contract: Automatically populated from FormRequest/Route input
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly Plan $plan,   // Automatically resolved via Model matching and findOrFail!
        public readonly Actor $actor, // Bound automatically to the authenticated user profile!
    ) {}

    // 2. Business Logic: Deep Container Injection supported on handle execution
    public function handle(PaymentGateway $gateway): User
    {
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        $gateway->subscribeToPlan($user, $this->plan);

        return $user;
    }
}

```

### 2. Executing from a Controller

Your controllers are left entirely unburdened by boilerplate. Pass the type-hinted Request instance straight into the `call()` framework:

```php
namespace App\Http\Controllers;

use App\Http\Requests\RegisterUserRequest;
use App\Architecture\Messages\RegisterUserAndSubscribe;

class RegistrationController extends Controller
{
    public function __invoke(RegisterUserRequest $request)
    {
        // 1. Fully Typehinted for your IDE 
        // 2. Intercepts the custom FormRequest and extracts $request->validated() data only
        // 3. Merges route params and routes execution to the internal handler
        $user = RegisterUserAndSubscribe::call($request);

        return response()->json($user, 201);
    }
}

```

### 3. Nesting Queries (Isolated Sub-calls)

If you need a compound dataset for a view or dashboard, you can cleanly nest messages. When explicit parameters or arrays are passed into `call()`, Hyperdrive isolates the execution context from the global HTTP state.

```php
namespace App\Architecture\Messages;

use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class UsersPageData extends Message
{
    public function handle(): array
    {
        return [
            // Explicit parameters bypass global request payloads completely
            'users' => GetUsers::call(['role_id' => 8]),
            'roles' => GetRoles::call(),
        ];
    }
}

```

---

## Deep-Dive: Parameter Prioritization Matrix

When a Message passes through the `MessageDispatcher`, parameters are extracted and layered dynamically. Arrays are merged from left to right, meaning **later keys overwrite matching previous keys** if a naming collision occurs:

```
┌──────────────────────────────────────────────────────────────────┐
│ 1. Request Payload Source                                        │
│    ├── FormRequest Instance  ──>  $request->validated()          │
│    └── Standard Request      ──>  $request->input()              │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                                 ▼ (Merged Over Payload)
┌──────────────────────────────────────────────────────────────────┐
│ 2. URL Route Placeholders    ──>  $request->route()->parameters()│
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                                 ▼ (Merged Over Route Parameters)
┌──────────────────────────────────────────────────────────────────┐
│ 3. Authenticated Context     ──>  ['actor' => auth()->user()]    │
└────────────────────────────────┬─────────────────────────────────┘
                                 │
                                 ▼ (Final Overrides Take Precedence)
┌──────────────────────────────────────────────────────────────────┐
│ 4. Explicit Method Calls     ──>  ::call(['key' => 'value'])     │
└──────────────────────────────────────────────────────────────────┘

```

### Coercion Specifications

The pipeline maps the merged dictionary against the Message's constructor rules via reflection:

* **Eloquent Models**: Looks for an incoming parameter key named after the parameter variable. If a primitive ID is found, it performs a strict `Model::findOrFail($value)` lookup. If an instance of that Model is already provided, it bypasses database querying entirely.
* **PHP Enums**: Backed enums are verified through `tryFrom($value)`. Unit enums are string-matched directly against `$case->name`. Failed lookups instantly generate a structured `ValidationException`.