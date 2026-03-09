<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_award_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/awards/' . $award->id . '/update', [
            'name' => 'Updated Medal Name',
        ]);

        $response->assertRedirect(route('admin.awards.list'));
        $this->assertDatabaseHas('tt_awards', [
            'id' => $award->id,
            'name' => 'Updated Medal Name',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->post('/admin/awards/' . $award->id . '/update', [
            'name' => 'Updated Medal Name',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
