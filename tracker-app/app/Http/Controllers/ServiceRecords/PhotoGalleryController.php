<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\ServiceRecords\Queries\GetPhotoGalleryQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the community photo gallery of recent event uploads.
 */
class PhotoGalleryController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $uploads = $this->bus->send(new GetPhotoGalleryQuery());

        return view('pages.service-records.photo-gallery', compact('uploads'));
    }
}
