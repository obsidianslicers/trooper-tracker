<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_faq_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $faq = Faq::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.faq.update', ['faq' => $faq->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/faq/Update')
            ->where('faq.id', $faq->id)
        );
    }

    public function test_invoke_requires_authentication(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->get(route('admin.faq.update', ['faq' => $faq->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
