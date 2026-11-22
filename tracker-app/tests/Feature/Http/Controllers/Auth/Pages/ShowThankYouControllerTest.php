<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowThankYouControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_thank_you_page(): void
    {
        $response = $this->get(route('auth.thank-you'));

        $response->assertOk();
        $response->assertViewIs('pages.auth.thank-you');
    }
}
