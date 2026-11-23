<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Organizations\PageData\OrganizationShareImagePageData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Handles the display of the organization image sharing page.
 *
 * This controller is responsible for rendering the organization image sharing view
 * that allows users to share organization images via various channels
 * such as social media, email, or direct links.
 */
class ShowOrganizationShareImageController extends PageController
{
    public function __invoke(Request $request): Response
    {
        $png = OrganizationShareImagePageData::call($request);

        abort_if($png === null, 404);

        return response($png)->header('Content-Type', 'image/png');
    }
}
