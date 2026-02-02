<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventUpload;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Handles event image uploads from troopers.
 *
 * This controller processes image uploads for events, creating both original
 * and thumbnail versions. Images are normalized to square canvases with
 * transparency and stored in the public storage disk.
 */
class UploadImageController extends MagicBusController
{
    /**
     * Handle the incoming image upload request.
     *
     * Validates uploaded images (max 4MB, PNG/JPG/JPEG/WEBP), normalizes them
     * to square transparent canvases, generates thumbnails (128x128), and stores
     * both versions. Creates EventUpload records linked to the trooper and event.
     *
     * @param  Request  $request  The incoming HTTP request with 'images' file array.
     * @param  Event  $event  The event to attach uploads to.
     * @return \Illuminate\Http\Response Response with updated upload display view and flash message header.
     */
    public function __invoke(Request $request, Event $event)
    {
        $trooper_id = $request->user()->id;

        // 4MB
        $request->validate([
            'images.*' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $manager = ImageManager::withDriver(config('tracker.image.driver'));

        $fails = false;

        $uploads = 0;

        foreach ($request->file('images', []) as $file)
        {
            //  original image
            $original_path = $file->store("uploads/events/{$event->id}/originals", 'public');

            try
            {
                $image = $manager->read($file->getPathname());

                // Normalize to square transparent canvas
                $size = max($image->width(), $image->height());
                $canvas = $manager->create($size, $size)->fill('rgba(0,0,0,0)');
                $canvas->place($image, 'center');

                // Thumbnail (128x128)
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
                $fails = true;

                Storage::disk('public')->delete($original_path);

                Log::error("Image upload failed for {$file->getClientOriginalName()}", ['error' => $e->getMessage()]);

                continue;
            }

            $event_upload = new EventUpload;

            $event_upload->event_id = $event->id;
            $event_upload->trooper_id = $trooper_id;
            $event_upload->image_path_lg = $original_path;
            $event_upload->image_path_sm = $thumbnail_path;
            $event_upload->is_administrative = false;

            $event_upload->save();

            $uploads += 1;
        }

        $message = json_encode([
            'message' => $uploads == 1 ? 'Image Uploaded!' : "{$uploads} Images Uploaded!",
            'type' => $fails ? 'danger' : 'success',
        ]);

        $is_administrative = false;

        $data = compact('event', 'is_administrative');

        return response()
            ->view('pages.events.inc.upload-display', $data)
            ->header('X-Flash-Message', $message);
    }
}
