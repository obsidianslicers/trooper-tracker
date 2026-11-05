<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Messages\ServiceRecords\Queries\GetPhotoGallery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class PhotoGalleryControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('service-records.photo-gallery'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_renders_gallery_view_with_uploads(): void
    {
        $trooper = Trooper::factory()->asMember()->withVerifiedEmail()->create();

        $uploads = new LengthAwarePaginator(collect(), 0, 24);

        $messages = $this->fakeMessages([GetPhotoGallery::class => $uploads]);

        $response = $this->actingAs($trooper)->get(route('service-records.photo-gallery'));

        $messages->assertCalled(GetPhotoGallery::class, fn (array $params): bool => true);

        $response->assertOk();
        $response->assertViewIs('pages.service-records.photo-gallery');
        $response->assertViewHas('uploads', $uploads);
    }
}
