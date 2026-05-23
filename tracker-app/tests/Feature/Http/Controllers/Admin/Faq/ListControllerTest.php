<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_faq_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Faq::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.faq.list');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.faq.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
