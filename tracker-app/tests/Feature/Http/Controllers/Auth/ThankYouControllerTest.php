<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThankYouControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_thank_you_page(): void
    {
        $response = $this->get(route('auth.thank-you'));

        $response->assertOk();
        $response->assertViewIs('pages.auth.thank-you');
    }
}
