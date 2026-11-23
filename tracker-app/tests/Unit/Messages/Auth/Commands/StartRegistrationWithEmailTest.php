<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Commands;

use App\Facades\TroopTracker;
use App\Messages\Auth\Commands\StartRegistrationWithEmail;
use App\Plugins\PluginLoginProviders;
use Illuminate\Support\Facades\Session;
use Tests\Fixtures\Auth\ForumLoginProvider;
use Tests\TestCase;

class StartRegistrationWithEmailTest extends TestCase
{
    protected function tearDown(): void
    {
        ForumLoginProvider::reset();

        parent::tearDown();
    }

    public function test_handle_starts_an_email_registration(): void
    {
        $subject = new StartRegistrationWithEmail;
        $result = $subject->handle(app(TroopTracker::class));

        $this->assertNull($result);
        $this->assertSame('email', Session::get('registration_auth.method'));
        $this->assertNull(Session::get('registration_auth.email'));
        $this->assertTrue(Session::get('registration_auth.expires_at')->isFuture());
    }

    public function test_handle_returns_the_required_provider_and_forgets_any_registration(): void
    {
        ForumLoginProvider::$required = true;
        $this->app->make(PluginLoginProviders::class)->add(ForumLoginProvider::class);
        Session::put('registration_auth', ['method' => 'email']);

        $subject = new StartRegistrationWithEmail;
        $result = $subject->handle(app(TroopTracker::class));

        $this->assertSame('forum', $result?->key());
        $this->assertFalse(Session::has('registration_auth'));
    }
}
