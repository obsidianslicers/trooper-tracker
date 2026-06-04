<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\EventUpload;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class EventImageUploadService
{
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function upload(Event $event, int $trooper_id, array $files, bool $is_administrative): EventImageUploadResult
    {
        $manager = ImageManager::withDriver(config('tracker.image.driver'));
        $uploads = 0;
        $failures = 0;

        foreach ($files as $file)
        {
            $original_path = $file->store("uploads/events/{$event->id}/originals", 'public');

            try
            {
                $image = $manager->read($file->getPathname());

                $size = max($image->width(), $image->height());
                $canvas = $manager->create($size, $size)->fill('rgba(0,0,0,0)');
                $canvas->place($image, 'center');

                $thumbnail = clone $canvas;
                $thumbnail->scaleDown(128, 128);

                $thumbnail_path = "uploads/events/{$event->id}/thumbnails/".pathinfo($file->hashName(), PATHINFO_FILENAME).'.png';

                Storage::disk('public')->put(
                    $thumbnail_path,
                    $thumbnail->encodeByExtension('png')
                );
            }
            catch (Exception $e)
            {
                $failures++;

                Storage::disk('public')->delete($original_path);

                Log::error("Image upload failed for {$file->getClientOriginalName()}", ['error' => $e->getMessage()]);

                continue;
            }

            EventUpload::create([
                EventUpload::EVENT_ID => $event->id,
                EventUpload::TROOPER_ID => $trooper_id,
                EventUpload::IMAGE_PATH_LG => $original_path,
                EventUpload::IMAGE_PATH_SM => $thumbnail_path,
                EventUpload::IS_ADMINISTRATIVE => $is_administrative,
            ]);

            $uploads++;
        }

        return new EventImageUploadResult($uploads, $failures);
    }
}
