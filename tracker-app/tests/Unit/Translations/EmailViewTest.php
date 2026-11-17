<?php

declare(strict_types=1);

namespace Tests\Unit\Translations;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class EmailViewTest extends TestCase
{
    private string $de_view = '';

    protected function tearDown(): void
    {
        if ($this->de_view !== '')
        {
            File::deleteDirectory(dirname($this->de_view, 2));
        }

        parent::tearDown();
    }

    public function test_email_view_uses_english_when_the_language_has_no_copy(): void
    {
        App::setLocale('fr');

        $this->assertSame('emails.en.events.trooper-signup', email_view('events.trooper-signup'));
    }

    public function test_email_view_uses_the_languages_own_copy(): void
    {
        $this->de_view = resource_path('views/emails/de/events/trooper-signup.blade.php');
        File::ensureDirectoryExists(dirname($this->de_view));
        File::put($this->de_view, 'Hallo');
        App::setLocale('de');

        $this->assertSame('emails.de.events.trooper-signup', email_view('events.trooper-signup'));
    }
}
