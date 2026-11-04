<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Closure;
use Hyperdrive\MessageDispatcher;
use Illuminate\Http\Request;
use PHPUnit\Framework\Assert;
use ReflectionClass;

/**
 * Swap the Hyperdrive dispatcher so selected messages return canned results.
 *
 * Messages that are not faked still run through the real dispatcher.
 */
trait FakesMessages
{
    /**
     * @param  array<class-string, mixed>  $results  Message class => result, or a Closure(array $params).
     */
    protected function fakeMessages(array $results): object
    {
        $fake = new class(new MessageDispatcher, $results) {
            /** @var array<int, array{0: string, 1: array}> */
            public array $calls = [];

            public function __construct(
                private readonly MessageDispatcher $dispatcher,
                private readonly array $results,
            ) {}

            public function handle(string $message_class, ?Request $request = null, array $params = []): mixed
            {
                if (!array_key_exists($message_class, $this->results))
                {
                    return $this->dispatcher->handle($message_class, $request, $params);
                }

                $this->calls[] = [$message_class, $this->withDefaults($message_class, $params)];
                $result = $this->results[$message_class];

                return $result instanceof Closure
                    ? $result($this->withDefaults($message_class, $params))
                    : $result;
            }

            private function withDefaults(string $message_class, array $params): array
            {
                $constructor = (new ReflectionClass($message_class))->getConstructor();

                foreach ($constructor?->getParameters() ?? [] as $parameter)
                {
                    $name = $parameter->getName();

                    if (!array_key_exists($name, $params) && $parameter->isDefaultValueAvailable())
                    {
                        $params[$name] = $parameter->getDefaultValue();
                    }
                }

                return $params;
            }

            /**
             * @return array<int, array<string, mixed>>
             */
            public function callsTo(string $message_class): array
            {
                return array_values(array_map(
                    fn (array $call): array => $call[1],
                    array_filter($this->calls, fn (array $call): bool => $call[0] === $message_class)
                ));
            }

            public function assertCalled(string $message_class, ?Closure $check = null, int $times = 1): void
            {
                $calls = $this->callsTo($message_class);

                if ($check !== null)
                {
                    $calls = array_filter($calls, $check);
                }

                Assert::assertCount($times, $calls, "Expected {$message_class} to be called {$times} time(s).");
            }

            public function assertNotCalled(string $message_class): void
            {
                Assert::assertSame([], $this->callsTo($message_class), "{$message_class} was called.");
            }
        };

        $this->instance(MessageDispatcher::class, $fake);

        return $fake;
    }
}
