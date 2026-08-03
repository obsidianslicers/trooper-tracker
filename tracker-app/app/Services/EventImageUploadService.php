<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\EventUpload;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Imagick;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

class EventImageUploadService
{
    private const HEIC_EXTENSIONS = ['heic', 'heif'];

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
            try
            {
                $this->processFile($event, $trooper_id, $file, $is_administrative, $manager);
                $uploads++;
            }
            catch (Exception $e)
            {
                $failures++;

                Log::error("Image upload failed for {$file->getClientOriginalName()}", ['error' => $e->getMessage()]);
            }
        }

        return new EventImageUploadResult($uploads, $failures);
    }

    private function processFile(Event $event, int $trooper_id, UploadedFile $file, bool $is_administrative, ImageManager $manager): void
    {
        $is_heic = in_array(strtolower((string) $file->getClientOriginalExtension()), self::HEIC_EXTENSIONS, true);
        $converted_bytes = $is_heic ? $this->convertHeicToPng($file) : null;
        $original_path = $this->storeOriginal($event, $file, $converted_bytes);

        try
        {
            $canvas = $this->buildSquareCanvas($manager, $manager->read($converted_bytes ?? $file->getPathname()));
            $thumbnail_path = $this->storeThumbnail($event, $file, $canvas);
        }
        catch (Exception $e)
        {
            Storage::disk('public')->delete($original_path);

            throw $e;
        }

        EventUpload::create([
            EventUpload::EVENT_ID => $event->id,
            EventUpload::TROOPER_ID => $trooper_id,
            EventUpload::IMAGE_PATH_LG => $original_path,
            EventUpload::IMAGE_PATH_SM => $thumbnail_path,
            EventUpload::IS_ADMINISTRATIVE => $is_administrative,
        ]);
    }

    private function convertHeicToPng(UploadedFile $file): string
    {
        if (! extension_loaded('imagick'))
        {
            throw new Exception('The Imagick PHP extension is required to process HEIC/HEIF images.');
        }

        $imagick = new Imagick($file->getPathname());
        $imagick->setImageFormat('png');

        return $imagick->getImageBlob();
    }

    private function storeOriginal(Event $event, UploadedFile $file, ?string $converted_bytes): string
    {
        $directory = "uploads/events/{$event->id}/originals";

        if ($converted_bytes === null)
        {
            return $file->store($directory, 'public');
        }

        $filename = pathinfo($file->hashName(), PATHINFO_FILENAME).'.png';

        Storage::disk('public')->put("{$directory}/{$filename}", $converted_bytes);

        return "{$directory}/{$filename}";
    }

    private function buildSquareCanvas(ImageManager $manager, ImageInterface $image): ImageInterface
    {
        $size = max($image->width(), $image->height());
        $canvas = $manager->create($size, $size)->fill('rgba(0,0,0,0)');
        $canvas->place($image, 'center');

        return $canvas;
    }

    private function storeThumbnail(Event $event, UploadedFile $file, ImageInterface $canvas): string
    {
        $thumbnail = clone $canvas;
        $thumbnail->scaleDown(128, 128);

        $thumbnail_path = "uploads/events/{$event->id}/thumbnails/".pathinfo($file->hashName(), PATHINFO_FILENAME).'.png';

        Storage::disk('public')->put($thumbnail_path, $thumbnail->encodeByExtension('png'));

        return $thumbnail_path;
    }
}
