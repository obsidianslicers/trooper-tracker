<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\ServiceRecords;

use App\Bus\MagicBus;
use App\Features\ServiceRecords\Queries\GetPhotoGalleryQuery;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class PhotoGalleryControllerTest extends TestCase
{
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

        $this->mock(MagicBus::class, function (MockInterface $mock) use ($uploads): void
        {
            $mock->shouldReceive('send')
                ->once()
                ->withArgs(fn (GetPhotoGalleryQuery $query): bool => true)
                ->andReturn($uploads);
        });

        $response = $this->actingAs($trooper)->get(route('service-records.photo-gallery'));

        $response->assertOk();
        $response->assertViewIs('pages.service-records.photo-gallery');
        $response->assertViewHas('uploads', $uploads);
    }
}
