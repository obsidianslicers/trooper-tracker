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
 * Class UpdateController
 *
 * Handles displaying the form to update an existing organization.
 * @package App\Http\Controllers\Admin\Organizations
 */
class UpdateImageController extends Controller
{
    /**
     * Handle the request to display the organization update page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to update an existing organization.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Organization $organization The organization to be updated.
     * @return View The rendered organization update view.
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

        $data = ['organization' => $organization];

        return view('pages.admin.organizations.image', $data);
    }
}
