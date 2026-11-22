<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards\Commands;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAwardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_award_and_redirects(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $award = Award::factory()->create();

        $response = $this->actingAs($trooper)->post('/admin/awards/'.$award->id, [
            'name' => 'Updated Medal Name',
        ]);

        $response->assertRedirect(route('admin.awards.index'));
        $this->assertDatabaseHas('tt_awards', [
            'id' => $award->id,
            'name' => 'Updated Medal Name',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $award = Award::factory()->create();

        $response = $this->post('/admin/awards/'.$award->id, [
            'name' => 'Updated Medal Name',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
