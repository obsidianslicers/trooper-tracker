<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Concerns;

use App\Services\EventImageUploadResult;
use Illuminate\Validation\ValidationException;

trait HandlesEventImageUploadResponses
{
    /**
     * @return array<string, array<int, string>>
     */
    private function eventImageUploadRules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'mimes:png,jpg,jpeg,webp,heic,heif', 'max:12288'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function eventImageUploadMessages(): array
    {
        return [
            'images.required' => 'Please choose at least one image to upload.',
            'images.array' => 'Please choose at least one image to upload.',
            'images.min' => 'Please choose at least one image to upload.',
            'images.*.mimes' => 'Only JPG, PNG, WEBP, and HEIC images can be uploaded.',
            'images.*.max' => 'Each image must be 12MB or smaller.',
        ];
    }

    private function eventImageUploadValidationMessage(ValidationException $e): string
    {
        $messages = $e->validator->errors()->all();

        return $messages[0] ?? 'Please upload JPG, PNG, WEBP, or HEIC images that are 12MB or smaller.';
    }

    private function eventImageUploadSuccessMessage(EventImageUploadResult $result): string
    {
        if (! $result->hasUploads())
        {
            return 'No images were uploaded. Please try JPG, PNG, WEBP, or HEIC images that are 12MB or smaller.';
        }

        $uploaded = $result->uploads === 1 ? '1 image uploaded' : "{$result->uploads} images uploaded";

        if ($result->hasFailures())
        {
            $failed = $result->failures === 1 ? '1 image failed' : "{$result->failures} images failed";

            return "{$uploaded}; {$failed}.";
        }

        return $result->uploads === 1 ? 'Image Uploaded!' : "{$result->uploads} Images Uploaded!";
    }

    /**
     * @return array{message: string, type: string, fadeOut: int}
     */
    private function eventImageUploadFlash(string $message, string $type): array
    {
        return [
            'message' => $message,
            'type' => $type,
            'fadeOut' => 5000,
        ];
    }
}
