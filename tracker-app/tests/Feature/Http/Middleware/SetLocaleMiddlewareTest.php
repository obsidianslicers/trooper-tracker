<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SetLocaleMiddleware;
use App\Models\Trooper;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tracker.locales' => ['en' => 'English', 'de' => 'Deutsch', 'es' => 'Español'],
            'app.locale' => 'es',
        ]);
    }

    private function runMiddleware(?Trooper $trooper): string
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $trooper);

        $subject = new SetLocaleMiddleware(new TranslationService);
        $subject->handle($request, fn () => response('ok'));

        return app()->getLocale();
    }

    public function test_handle_uses_site_default_for_guests(): void
    {
        $this->assertSame('es', $this->runMiddleware(null));
    }

    public function test_handle_uses_troopers_language(): void
    {
        $trooper = Trooper::factory()->create([Trooper::LOCALE => 'de']);

        $this->assertSame('de', $this->runMiddleware($trooper));
    }

    public function test_handle_ignores_a_language_the_site_no_longer_offers(): void
    {
        $trooper = Trooper::factory()->create([Trooper::LOCALE => 'fr']);

        $this->assertSame('es', $this->runMiddleware($trooper));
    }

    public function test_handle_falls_back_to_english_when_site_default_is_unsupported(): void
    {
        config(['app.locale' => 'xx']);

        $this->assertSame('en', $this->runMiddleware(null));
    }
}
