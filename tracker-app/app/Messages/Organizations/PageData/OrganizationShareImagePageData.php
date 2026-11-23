<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Models\Organization;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * The organization's logo as a 200x200 PNG for link previews, or null when there is no image.
 *
 * Falls back to the default organization image when the organization has no logo of its own.
 *
 * @method static string|null call(Organization $organization)
 */
final class OrganizationShareImagePageData extends Message
{
    public const SIZE = 200;

    public function __construct(private readonly Organization $organization) {}

    public function handle(): ?string
    {
        $path = public_path(DEFAULT_ORGANIZATION_IMAGE_URL);
        $image_path = $this->organization->image_path_lg;

        if ($image_path && Storage::disk('public')->exists($image_path))
        {
            $path = Storage::disk('public')->path($image_path);
        }

        if (!file_exists($path))
        {
            return null;
        }

        $image = (new ImageManager(new Driver))->read($path);
        $image->cover(self::SIZE, self::SIZE);

        return (string) $image->encodeByExtension('png');
    }
}
