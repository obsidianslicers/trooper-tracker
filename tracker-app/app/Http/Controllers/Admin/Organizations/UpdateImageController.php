<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Handles organization logo image uploads.
 *
 * This controller processes organization logo uploads, normalizing images to
 * square transparent canvases and creating both large (128x128) and small (32x32)
 * versions stored in the public storage disk.
 */
class UpdateImageController extends Controller
{
    /**
     * Handle the organization logo upload request.
     *
     * Validates the uploaded logo image (max 2MB, PNG/JPG/JPEG/WEBP), normalizes it
     * to a square transparent canvas, creates large (128x128) and small (32x32) versions,
     * stores them in public storage, updates the organization's image paths, and returns
     * the image display view.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Organization $organization The organization to update the logo for.
     * @return View The organization image display view (pages.admin.organizations.image).
     */
    public function __invoke(Request $request, Organization $organization): View
    {
        $this->authorize('update', $organization);

        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $file = $request->file('logo');

        // Create manager with GD or Imagick driver
        $manager = new ImageManager(new Driver());

        // Read the uploaded file
        $image = $manager->read($file->getPathname());

        // Force PNG output for transparency
        $image->encodeByExtension('png');

        // Normalize to square transparent canvas
        $size = max($image->width(), $image->height());
        $canvas = $manager->create($size, $size)->fill('rgba(0,0,0,0)');
        $canvas->place($image, 'center');

        $path = "uploads/organizations/{$organization->id}";

        // Large (128x128)
        $large = clone $canvas;
        $large->scaleDown(128, 128);
        Storage::disk('public')->put("{$path}-128x128.png", $large->encodeByExtension('png'));

        // Small (32x32)
        $small = clone $canvas;
        $small->scaleDown(32, 32);
        Storage::disk('public')->put("{$path}-32x32.png", $small->encodeByExtension('png'));

        $organization->image_path_lg = "{$path}-128x128.png";
        $organization->image_path_sm = "{$path}-32x32.png";

        $organization->save();

        $data = compact('organization');

        return view('pages.admin.organizations.image', $data);
    }
}
