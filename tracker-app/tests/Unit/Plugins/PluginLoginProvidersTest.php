<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Auth\GoogleLoginProvider;
use App\Plugins\PluginLoginProviders;
use InvalidArgumentException;
use stdClass;
use Tests\Fixtures\Auth\ForumLoginProvider;
use Tests\TestCase;

class PluginLoginProvidersTest extends TestCase
{
    private PluginLoginProviders $subject;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'google-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect' => 'https://example.com/google/callback',
        ]);

        $this->subject = new PluginLoginProviders($this->app);
    }

    protected function tearDown(): void
    {
        ForumLoginProvider::reset();

        parent::tearDown();
    }

    public function test_google_is_built_in(): void
    {
        $this->assertInstanceOf(GoogleLoginProvider::class, $this->subject->find('google'));
        $this->assertSame(['google'], $this->keys($this->subject->available()));
    }

    public function test_google_is_unavailable_until_configured(): void
    {
        config(['services.google.redirect' => null]);

        $this->assertSame([], $this->subject->available());
        $this->assertSame('Google login is not available.', $this->subject->unavailableReason('google'));
    }

    public function test_add_rejects_a_class_that_is_not_a_login_provider(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->subject->add(stdClass::class);
    }

    public function test_added_providers_are_available_once_configured(): void
    {
        $this->subject->add(ForumLoginProvider::class);

        $this->assertSame(['google', 'forum'], $this->keys($this->subject->available()));
        $this->assertNull($this->subject->unavailableReason('forum'));

        ForumLoginProvider::$configured = false;

        $this->assertSame(['google'], $this->keys($this->subject->available()));
    }

    public function test_a_required_provider_turns_the_others_off(): void
    {
        ForumLoginProvider::$required = true;
        $this->subject->add(ForumLoginProvider::class);

        $this->assertSame('forum', $this->subject->required()?->key());
        $this->assertSame(['forum'], $this->keys($this->subject->available()));
        $this->assertSame(
            'Troop Tracker is configured to use Florida Garrison for login.',
            $this->subject->unavailableReason('google'),
        );
    }

    public function test_a_required_provider_that_is_not_configured_is_not_required(): void
    {
        ForumLoginProvider::$required = true;
        ForumLoginProvider::$configured = false;
        $this->subject->add(ForumLoginProvider::class);

        $this->assertNull($this->subject->required());
        $this->assertSame(['google'], $this->keys($this->subject->available()));
    }

    public function test_unknown_providers_are_unavailable(): void
    {
        $this->assertSame('That login is not available.', $this->subject->unavailableReason('myspace'));
    }

    /**
     * @param  array<int, \App\Contracts\LoginProvider>  $providers
     * @return array<int, string>
     */
    private function keys(array $providers): array
    {
        return array_map(fn ($provider): string => $provider->key(), $providers);
    }
}
