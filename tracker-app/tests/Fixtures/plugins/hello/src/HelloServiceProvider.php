<?php

declare(strict_types=1);

namespace HelloPlugin;

use App\Events\EventPosted;
use App\Plugins\PluginServiceProvider;
use Hyperdrive\Message;

class HelloServiceProvider extends PluginServiceProvider
{
    /**
     * @var array<int, string>
     */
    public static array $handled = [];

    /**
     * @var array<int, int>
     */
    public static array $posted = [];

    public function bootPlugin(): void
    {
        $this->addToSlot('test.slot', 'hello::greeting', fn (array $context): array => [
            'greeting' => $this->setting('greeting'),
        ]);

        $this->addToSlot(
            'account.profile.sections',
            'hello::greeting',
            fn (array $context): array => [
                'greeting' => 'Welcome back',
                'name' => $context['actor']?->display_name,
            ],
        );

        $this->afterMessage(Message::class, function (Message $message): void
        {
            self::$handled[] = $message::class;
        });

        $this->listen(EventPosted::class, function (EventPosted $posted): void
        {
            self::$posted[] = $posted->event->id;
        });
    }
}
