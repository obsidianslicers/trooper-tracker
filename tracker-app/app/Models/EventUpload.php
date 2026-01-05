<?php

namespace App\Models;

use App\Models\Base\EventUpload as BaseEventUpload;
use App\Models\Concerns\HasTrooperStamps;
use App\Models\Scopes\HasEventUploadScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    public function getUrlAttribute(): string
    {
        // If it contains a slash, assume it's already a path
        if (str_contains($this->filename, '/'))
        {
            return $this->filename;
        }

        return url('images/uploads/' . $this->filename);
    }
}
