<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Sentry\Tests;

use App\Enums\SystemCheckStatus;
use App\Plugins\PluginManager;
use App\Plugins\PluginSystemChecks;
use App\Services\SystemCheckResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Tests\TestCase;

class SentryPluginTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_DSN = 'https://5e7e7e7e@o1.ingest.sentry.io/111';

    private const BROWSER_DSN = 'https://b7a8b9c0@o1.ingest.sentry.io/222';

    private PluginManager $plugins;

    /** @var TransportInterface&object{events: list<Event>} */
    private TransportInterface $transport;

    protected function setUp(): void
    {
        parent::setUp();

        if (!function_exists('Sentry\init'))
        {
            $this->markTestSkipped(
                'sentry/sentry is not installed. Enable the plugin and deploy, or run composer require sentry/sentry.'
            );
        }

        $this->plugins = app(PluginManager::class);
    }

    protected function tearDown(): void
    {
        if (function_exists('Sentry\init'))
        {
            SentrySdk::init();
        }

        parent::tearDown();
    }

    public function test_a_logged_exception_is_sent_with_the_trooper_id_and_nothing_else_about_them(): void
    {
        $this->enableSentry(['dsn' => self::SERVER_DSN, 'environment' => 'staging']);
        //  joined at runtime, so Sentry's copy of these source lines can't contain it
        $email = implode('@', ['jane.trooper', 'example.test']);

        Log::error('boom', [
            'exception' => $this->exceptionThrownWith($email),
            'status_code' => 500,
            'method' => 'POST',
            'user_id' => 42,
            'ip' => '203.0.113.9',
            'input' => ['name' => 'Jane Trooper', 'email' => $email],
        ]);

        $this->assertCount(1, $this->transport->events);
        $event = $this->transport->events[0];
        $this->assertSame('boom', $event->getExceptions()[0]->getValue());
        $this->assertSame('staging', $event->getEnvironment());
        $this->assertSame('42', $event->getUser()?->getId());
        $this->assertNull($event->getUser()?->getIpAddress());
        $this->assertSame(['status_code' => 500, 'method' => 'POST'], $event->getExtra());
        //  not in the extras, nor in the function arguments kept on the stack frames
        $this->assertStringNotContainsString($email, serialize($event));
    }

    public function test_messages_without_an_exception_and_warnings_are_not_sent(): void
    {
        $this->enableSentry(['dsn' => self::SERVER_DSN]);

        Log::error('just a message');
        Log::warning('a warning', ['exception' => new RuntimeException('minor')]);

        $this->assertSame([], $this->transport->events);
    }

    public function test_reported_exceptions_reach_sentry(): void
    {
        $this->enableSentry(['dsn' => self::SERVER_DSN]);

        report(new RuntimeException('from report()'));

        $this->assertSame('from report()', $this->transport->events[0]->getExceptions()[0]->getValue());
    }

    public function test_the_server_dsn_falls_back_to_the_env_setting(): void
    {
        config(['plugins.sentry.dsn' => self::SERVER_DSN]);
        $this->enableSentry();

        $this->assertSame(self::SERVER_DSN, (string) SentrySdk::getCurrentHub()->getClient()?->getOptions()->getDsn());
    }

    public function test_nothing_is_set_up_without_a_server_dsn(): void
    {
        $this->enableSentry();

        $this->assertNull(SentrySdk::getCurrentHub()->getClient());
    }

    public function test_pages_load_the_browser_sdk_once_a_browser_dsn_is_set(): void
    {
        $this->enableSentry(['browser_dsn' => self::BROWSER_DSN]);

        $this->get(route('auth.login'))
            ->assertOk()
            ->assertSee('https://browser.sentry-cdn.com/11.10.0/bundle.min.js', false)
            ->assertSee('b7a8b9c0@o1.ingest.sentry.io', false)
            ->assertSee('webhooks', false)
            ->assertSee('sha384-', false);
    }

    public function test_pages_leave_the_browser_sdk_out_without_a_browser_dsn(): void
    {
        $this->enableSentry(['dsn' => self::SERVER_DSN]);

        $this->get(route('auth.login'))
            ->assertOk()
            ->assertDontSee('browser.sentry-cdn.com', false);
    }

    public function test_the_tunnel_forwards_an_envelope_for_the_browser_dsn_to_its_project(): void
    {
        Http::fake(['*' => Http::response('{"id":"abc"}', 200)]);
        $this->enableSentry(['browser_dsn' => self::BROWSER_DSN]);
        $envelope = $this->envelope(self::BROWSER_DSN);

        $response = $this->call('POST', '/webhooks/sentry', [], [], [], ['CONTENT_TYPE' => 'text/plain'], $envelope);

        $response->assertOk();
        $this->assertSame('{"id":"abc"}', $response->getContent());
        Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://o1.ingest.sentry.io/api/222/envelope/'
            && $request->body() === $envelope
            && $request->hasHeader('Content-Type', 'application/x-sentry-envelope'));
    }

    public function test_the_tunnel_refuses_envelopes_for_any_other_dsn(): void
    {
        Http::fake();
        $this->enableSentry(['browser_dsn' => self::BROWSER_DSN]);

        foreach ([
            'https://b7a8b9c0@evil.example/222',
            'https://b7a8b9c0@o1.ingest.sentry.io/999',
            'https://0d0d0d0d@o1.ingest.sentry.io/222',
            'not a dsn',
        ] as $dsn)
        {
            $this->call('POST', '/webhooks/sentry', [], [], [], [], $this->envelope($dsn))->assertForbidden();
        }

        $this->call('POST', '/webhooks/sentry', [], [], [], [], "garbage\n{}")->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_the_tunnel_is_closed_without_a_browser_dsn_or_when_turned_off(): void
    {
        Http::fake();
        $this->enableSentry(['dsn' => self::SERVER_DSN]);
        $this->call('POST', '/webhooks/sentry', [], [], [], [], $this->envelope(self::BROWSER_DSN))->assertNotFound();

        $this->plugins->updateSettings('sentry', ['browser_dsn' => self::BROWSER_DSN, 'tunnel' => false]);
        $this->call('POST', '/webhooks/sentry', [], [], [], [], $this->envelope(self::BROWSER_DSN))->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_the_tunnel_refuses_empty_and_oversized_envelopes_and_floods(): void
    {
        Http::fake();
        $this->enableSentry(['browser_dsn' => self::BROWSER_DSN]);

        $this->call('POST', '/webhooks/sentry', [], [], [], [], '')->assertStatus(400);
        $this->call('POST', '/webhooks/sentry', [], [], [], [], $this->envelope(self::BROWSER_DSN).str_repeat('x', 1_048_576))
            ->assertStatus(413);

        for ($i = 0; $i < 58; $i++)
        {
            $this->call('POST', '/webhooks/sentry', [], [], [], [], $this->envelope(self::BROWSER_DSN))->assertOk();
        }

        $this->call('POST', '/webhooks/sentry', [], [], [], [], $this->envelope(self::BROWSER_DSN))->assertStatus(429);
    }

    public function test_system_check_reports_the_server_dsn(): void
    {
        $this->enableSentry();
        $this->assertSame(SystemCheckStatus::WARN, $this->systemCheck()->status);

        $this->plugins->updateSettings('sentry', ['dsn' => self::SERVER_DSN]);
        $this->assertSame(SystemCheckStatus::PASS, $this->systemCheck()->status);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function enableSentry(array $settings = []): void
    {
        //  saved first: the plugin reads them when it boots, as each request does
        //  (updateSettings() turns an unlisted boolean off, so the tunnel default is given here)
        $this->plugins->updateSettings('sentry', $settings + ['tunnel' => true]);
        $this->plugins->enable('sentry');
        $this->plugins->load($this->plugins->findOrFail('sentry'));

        $this->transport = new class implements TransportInterface {
            /** @var list<Event> */
            public array $events = [];

            public function send(Event $event): Result
            {
                $this->events[] = $event;

                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result
            {
                return new Result(ResultStatus::success());
            }
        };

        //  keep the options the plugin set, but send to the fake transport
        $client = SentrySdk::getCurrentHub()->getClient();

        if ($client !== null)
        {
            SentrySdk::getCurrentHub()->bindClient(
                (new ClientBuilder($client->getOptions()))->setTransport($this->transport)->getClient()
            );
        }
    }

    private function exceptionThrownWith(string $email): RuntimeException
    {
        try
        {
            (fn (string $address) => throw new RuntimeException('boom'))($email);
        }
        catch (RuntimeException $exception)
        {
            return $exception;
        }
    }

    private function envelope(string $dsn): string
    {
        return json_encode(['dsn' => $dsn, 'sent_at' => '2026-11-11T00:00:00Z'])."\n"
            .json_encode(['type' => 'event'])."\n"
            .json_encode(['message' => 'hello']);
    }

    private function systemCheck(): SystemCheckResult
    {
        return app(PluginSystemChecks::class)->run()->keyBy('label')['Sentry server DSN'];
    }
}
