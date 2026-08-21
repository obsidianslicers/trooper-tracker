<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares;

use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Handles the display of the organization image sharing page.
 *
 * This controller is responsible for rendering the organization image sharing view
 * that allows users to share organization images via various channels
 * such as social media, email, or direct links.
 */
class ShareOrganizationImageController extends MagicBusController
{
    /**
     * Handle the incoming request to display the organization image sharing page.
     *
     * This method retrieves the specified organization and renders the sharing
     * view where users can generate and copy shareable links or share
     * directly to social media platforms.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Organization  $organization  The organization whose image is to be shared
     * @return Response The response containing the image
     */
    public function __invoke(Request $request, Organization $organization): Response
    {
        $path = public_path(DEFAULT_ORGANIZATION_IMAGE_URL);

        if ($organization->image_path_lg && Storage::disk('public')->exists($organization->image_path_lg))
        {
            $path = Storage::disk('public')->path($organization->image_path_lg);
        }

        if (!file_exists($path))
        {
            abort(404);
        }

        $manager = new ImageManager(new Driver);
        $image = $manager->read($path);
        $image->cover(200, 200);

        return response((string) $image->encodeByExtension('png'))->header('Content-Type', 'image/png');
    }
}
