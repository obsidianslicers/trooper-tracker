<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Models\EventUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A photo the trooper is tagged in, on the service record's Photos tab.
 *
 * @property EventUpload $resource
 */
class TaggedUploadResource extends JsonResource
{
    /**
     * @return array{id: int, small_url: string, large_url: string}
     */
    public function toArray(Request $request): array
    {
        return [
            EventUpload::ID => $this->resource->id,
            'small_url' => $this->resource->small_url,
            'large_url' => $this->resource->large_url,
        ];
    }
}
