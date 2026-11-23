<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\PhotoGalleryPageData;
use App\Messages\ServiceRecords\Queries\GetPhotoGallery;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class PhotoGalleryPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_returns_the_gallery_uploads(): void
    {
        $uploads = collect(['photo']);
        $this->fakeMessages([GetPhotoGallery::class => $uploads]);

        $subject = new PhotoGalleryPageData;

        $this->assertSame(['uploads' => $uploads], $subject->handle());
    }
}
