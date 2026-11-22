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
            'images.*' => ['required', 'mimes:png,jpg,jpeg,webp,heic,heif', 'max:'.$this->eventImageMaxUploadKb()],
        ];
    }

    private function eventImageMaxUploadKb(): int
    {
        return (int) config('tracker.image.max_upload_kb', 51200);
    }

    private function eventImageMaxUploadLabel(): string
    {
        $megabytes = round($this->eventImageMaxUploadKb() / 1024, 1);

        return rtrim(rtrim(number_format($megabytes, 1, '.', ''), '0'), '.').'MB';
    }

    /**
     * @return array<string, string>
     */
    private function eventImageUploadMessages(): array
    {
        return [
            'images.required' => __('events.Please choose at least one image to upload.'),
            'images.array' => __('events.Please choose at least one image to upload.'),
            'images.min' => __('events.Please choose at least one image to upload.'),
            'images.*.mimes' => __('events.Only JPG, PNG, WEBP, and HEIC images can be uploaded.'),
            'images.*.max' => __('events.Each image must be :size or smaller.', ['size' => $this->eventImageMaxUploadLabel()]),
            'images.*.uploaded' => __('events.That image failed to upload. Please try a smaller photo or check your connection.'),
        ];
    }

    private function eventImageUploadValidationMessage(ValidationException $e): string
    {
        $messages = $e->validator->errors()->all();

        return $messages[0]
            ?? __(
                'events.Please upload JPG, PNG, WEBP, or HEIC images that are :size or smaller.',
                ['size' => $this->eventImageMaxUploadLabel()]
            );
    }

    private function eventImageUploadSuccessMessage(EventImageUploadResult $result): string
    {
        if (! $result->hasUploads())
        {
            return __(
                'events.No images were uploaded. Please try JPG, PNG, WEBP, or HEIC images that are :size or smaller.',
                ['size' => $this->eventImageMaxUploadLabel()]
            );
        }

        $uploaded = trans_choice('events.:count image uploaded|:count images uploaded', $result->uploads);

        if ($result->hasFailures())
        {
            $failed = trans_choice('events.:count image failed|:count images failed', $result->failures);

            return __('events.:uploaded; :failed.', ['uploaded' => $uploaded, 'failed' => $failed]);
        }

        return trans_choice('events.Image Uploaded!|:count Images Uploaded!', $result->uploads);
    }
}
