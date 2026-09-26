<?php

declare(strict_types=1);

namespace Tests\Unit\Hyperdrive;

use App\Models\Trooper;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Hyperdrive\MessageDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

final class MessageDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(MessageDispatcher::class, static fn(): MessageDispatcher => new MessageDispatcher());
    }

    public function test_message_call_normalizes_positional_arguments_and_coerces_scalars(): void
    {
        $result = ScalarMessage::call('  7 ', '  Leia  ', 'true');

        $this->assertSame('7:Leia:true', $result);
    }

    public function test_dispatcher_merges_query_route_auth_and_explicit_parameters(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->actingAs($trooper);

        $request = Request::create('/items/4?item_id=3&label=query');
        $request->setRouteResolver(static function (): object
        {
            return new class
            {
                public function parameters(): array
                {
                    return ['item_id' => 4];
                }
            };
        });

        $result = OverrideMessage::call($request, ['item_id' => '9', 'label' => 'explicit']);

        $this->assertSame('9:explicit:' . $trooper->getKey(), $result);
    }

    public function test_dispatcher_resolves_backed_and_unit_enum_values(): void
    {
        $result = EnumMessage::call([
            'status' => 'published',
            'visibility' => 'PRIVATE',
        ]);

        $this->assertSame('published:PRIVATE', $result);
    }

    public function test_dispatcher_resolves_a_model_instance_and_model_identifier(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $subject = app(MessageDispatcher::class);

        $from_identifier = $subject->handle(ModelMessage::class, null, [
            'trooper' => $trooper->getKey(),
        ]);
        $from_instance = $subject->handle(ModelMessage::class, null, ['trooper' => $trooper]);

        $this->assertSame($trooper->getKey(), $from_identifier);
        $this->assertSame($trooper->getKey(), $from_instance);
    }

    public function test_dispatcher_injects_the_authenticated_actor(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->actingAs($trooper);

        $result = ActorMessage::call();

        $this->assertSame($trooper->getKey(), $result);
    }

    public function test_dispatcher_rejects_missing_and_invalid_parameters(): void
    {
        $subject = app(MessageDispatcher::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('required');

        $subject->handle(RequiredMessage::class);
    }

    public function test_dispatcher_rejects_an_invalid_enum_and_missing_model(): void
    {
        $subject = app(MessageDispatcher::class);

        try
        {
            $subject->handle(EnumMessage::class, null, ['status' => 'unknown', 'visibility' => 'PUBLIC']);
            $this->fail('An invalid enum should be rejected.');
        }
        catch (InvalidArgumentException $exception)
        {
            $this->assertStringContainsString('invalid', strtolower($exception->getMessage()));
        }

        $this->expectException(InvalidArgumentException::class);
        $subject->handle(ModelMessage::class, null, ['trooper' => 999999]);
    }

    public function test_dispatcher_wraps_transactional_messages_in_a_database_transaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static fn(callable $callback): mixed => $callback());

        $result = app(MessageDispatcher::class)->handle(TransactionalMessage::class);

        $this->assertSame('committed', $result);
    }

    public function test_dispatcher_detects_circular_message_calls(): void
    {
        $this->expectExceptionMessage('Circular message call detected');

        CircularMessage::call();
    }
}

enum TestStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

enum TestVisibility
{
    case PUBLIC;
    case PRIVATE;
}

final class ScalarMessage extends Message
{
    public function __construct(
        private readonly int $count,
        private readonly string $label,
        private readonly bool $enabled,
    ) {
    }

    public function handle(): string
    {
        return "{$this->count}:{$this->label}:" . ($this->enabled ? 'true' : 'false');
    }
}

final class OverrideMessage extends Message
{
    public function __construct(
        private readonly int $item_id,
        private readonly string $label,
        private readonly Actor $actor,
    ) {
    }

    public function handle(): string
    {
        return "{$this->item_id}:{$this->label}:" . $this->actor->getAuthIdentifier();
    }
}

final class EnumMessage extends Message
{
    public function __construct(
        private readonly TestStatus $status,
        private readonly TestVisibility $visibility,
    ) {
    }

    public function handle(): string
    {
        return "{$this->status->value}:{$this->visibility->name}";
    }
}

final class ModelMessage extends Message
{
    public function __construct(private readonly Trooper $trooper)
    {
    }

    public function handle(): int
    {
        return $this->trooper->getKey();
    }
}

final class ActorMessage extends Message
{
    public function __construct(private readonly Actor $actor)
    {
    }

    public function handle(): int
    {
        return $this->actor->getAuthIdentifier();
    }
}

final class RequiredMessage extends Message
{
    public function __construct(private readonly string $required)
    {
    }

    public function handle(): string
    {
        return $this->required;
    }
}

final class TransactionalMessage extends Message
{
    use ShouldBeTransactional;

    public function handle(): string
    {
        return 'committed';
    }
}

final class CircularMessage extends Message
{
    public function handle(): mixed
    {
        return self::call();
    }
}