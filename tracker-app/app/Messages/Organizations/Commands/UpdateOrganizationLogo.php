<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Commands;

use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Stores an organization's logo as square transparent PNGs, 128x128 and 32x32.
 *
 * @method static Organization call(Organization $organization, UploadedFile $logo)
 */
final class UpdateOrganizationLogo extends Message
{
    public function __construct(
        private readonly Organization $organization,
        private readonly UploadedFile $logo,
    ) {}

    public function handle(): Organization
    {
        $manager = new ImageManager(new Driver);

        $image = $manager->read($this->logo->getPathname());
        $image->encodeByExtension('png');

        $size = max($image->width(), $image->height());
        $canvas = $manager->create($size, $size)->fill('rgba(0,0,0,0)');
        $canvas->place($image, 'center');

        $path = "uploads/organizations/{$this->organization->id}";

        $large = clone $canvas;
        $large->scaleDown(128, 128);
        Storage::disk('public')->put("{$path}-128x128.png", $large->encodeByExtension('png'));

        $small = clone $canvas;
        $small->scaleDown(32, 32);
        Storage::disk('public')->put("{$path}-32x32.png", $small->encodeByExtension('png'));

        $this->organization->image_path_lg = "{$path}-128x128.png";
        $this->organization->image_path_sm = "{$path}-32x32.png";
        $this->organization->save();

        return $this->organization;
    }
}
