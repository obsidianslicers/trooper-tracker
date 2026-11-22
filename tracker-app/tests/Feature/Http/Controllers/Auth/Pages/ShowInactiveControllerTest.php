<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth\Pages;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowInactiveControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_inactive_page(): void
    {
        $response = $this->get(route('auth.inactive'));

        $response->assertOk();
        $response->assertViewIs('pages.auth.inactive');
    }
}
