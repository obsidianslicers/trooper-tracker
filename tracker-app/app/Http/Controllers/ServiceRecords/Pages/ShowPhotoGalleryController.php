<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\PhotoGalleryPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays the community photo gallery of recent event uploads.
 */
class ShowPhotoGalleryController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = PhotoGalleryPageData::call($request);

        return Inertia::render('service-records/PhotoGallery', $data);
    }
}
