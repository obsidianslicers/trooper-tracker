<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\EventUpload as BaseEventUpload;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventUploadScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

/**
 * Represents a photo or file upload associated with an event.
 *
 * This model tracks photos and other files uploaded for events, storing metadata
 * about the upload and its association with troopers who appear in the media.
 */
class EventUpload extends BaseEventUpload
{
    use HasEventUploadScopes;
    use HasFactory;
    use HasTrooperStamps;

    /**
     * Get the URL for the uploaded file.
     *
     * If the filename contains a slash, it's treated as a complete path.
     * Otherwise, it's assumed to be in the 'images/uploads/' directory.
     *
     * @return string The full URL to the uploaded file
     */
    public function getSmallUrlAttribute(): string
    {
        // If it contains a slash, assume it's already a path
        if (str_contains($this->image_path_sm, '/'))
        {
            return Storage::url($this->image_path_sm);
        }

        return url('images/uploads/'.$this->image_path_sm);
    }

    /**
     * Get the URL for the large version of the uploaded file.
     *
     * If the filename contains a slash, it's treated as a complete path.
     * Otherwise, it's assumed to be in the 'images/uploads/' directory.
     *
     * @return string The full URL to the large uploaded file
     */
    public function getLargeUrlAttribute(): string
    {
        // If it contains a slash, assume it's already a path
        if (str_contains($this->image_path_lg, '/'))
        {
            return Storage::url($this->image_path_lg);
        }

        return url('images/uploads/'.$this->image_path_lg);
    }

    /**
     * Whether the full-size photo is still on disk. Legacy uploads (a bare filename) live
     * in public/images/uploads; newer uploads are paths on the public disk.
     */
    public function hasOriginalFile(): bool
    {
        return self::fileExists($this->image_path_lg);
    }

    /**
     * The URL to show the photo at full size in places outside the tracker, such as the
     * public API slideshow. Legacy uploads prefer the old tracker's resized copy when present.
     */
    public function getDisplayUrlAttribute(): string
    {
        if (!str_contains($this->image_path_lg, '/'))
        {
            $resized = 'resize/'.pathinfo($this->image_path_lg, PATHINFO_FILENAME).'.jpg';

            if (Storage::disk('legacy_uploads')->exists($resized))
            {
                return url('images/uploads/'.$resized);
            }
        }

        return $this->large_url;
    }

    private static function fileExists(?string $path): bool
    {
        if ($path === null || $path === '')
        {
            return false;
        }

        $disk = str_contains($path, '/') ? 'public' : 'legacy_uploads';

        return Storage::disk($disk)->exists($path);
    }
}
