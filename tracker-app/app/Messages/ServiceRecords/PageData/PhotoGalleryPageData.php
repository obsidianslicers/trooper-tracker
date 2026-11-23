<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\Queries\GetPhotoGallery;
use Hyperdrive\Message;

/**
 * Photos troopers uploaded from their events.
 *
 * @method static array<string, mixed> call()
 */
final class PhotoGalleryPageData extends Message
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'uploads' => GetPhotoGallery::call(),
        ];
    }
}
