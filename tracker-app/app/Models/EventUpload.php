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
}
