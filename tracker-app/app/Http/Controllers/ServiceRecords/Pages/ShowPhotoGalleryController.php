<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\PhotoGalleryPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the community photo gallery of recent event uploads.
 */
class ShowPhotoGalleryController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = PhotoGalleryPageData::call($request);

        return view('pages.service-records.photo-gallery', $data);
    }
}
