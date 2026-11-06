<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\Queries\GetPhotoGallery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the community photo gallery of recent event uploads.
 */
class PhotoGalleryController extends PageController
{
    public function __invoke(Request $request): View
    {
        $uploads = GetPhotoGallery::call();

        return view('pages.service-records.photo-gallery', compact('uploads'));
    }
}
