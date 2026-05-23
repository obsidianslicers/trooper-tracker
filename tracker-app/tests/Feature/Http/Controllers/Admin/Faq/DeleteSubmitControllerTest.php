<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_soft_deletes_faq_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $faq = Faq::factory()->create();

        $response = $this->actingAs($trooper)->post(route('admin.faq.delete', ['faq' => $faq->id]));

        $response->assertRedirect(route('admin.faq.list'));
        $this->assertSoftDeleted('tt_faq', [Faq::ID => $faq->id]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $faq = Faq::factory()->create();

        $response = $this->post(route('admin.faq.delete', ['faq' => $faq->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
