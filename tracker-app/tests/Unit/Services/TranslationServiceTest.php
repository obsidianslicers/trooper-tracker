<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TranslationService;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    public function test_is_supported_checks_configured_locales(): void
    {
        config(['tracker.locales' => ['en' => 'English', 'de' => 'Deutsch']]);
        $subject = new TranslationService;

        $this->assertTrue($subject->isSupported('de'));
        $this->assertFalse($subject->isSupported('fr'));
        $this->assertFalse($subject->isSupported(null));
    }

    public function test_groups_lays_translations_over_english(): void
    {
        app('translator')->addLines(['fixture.Hello' => 'Hello', 'fixture.Bye' => 'Bye'], 'en');
        app('translator')->addLines(['fixture.Hello' => 'Hallo'], 'de');
        App::setLocale('de');

        $result = (new TranslationService)->groups('fixture');

        $this->assertSame(['Hello' => 'Hallo', 'Bye' => 'Bye'], $result);
    }

    public function test_groups_returns_empty_for_unknown_group(): void
    {
        $this->assertSame([], (new TranslationService)->groups('no-such-group'));
    }
}
