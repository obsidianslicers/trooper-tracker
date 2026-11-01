<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemCheckStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImageUploadSystemCheckService
{
    public const LABEL_FILE_UPLOADS = 'PHP file uploads enabled';

    public const LABEL_TEMP_DIR = 'Upload temp directory writable';

    public const LABEL_STORAGE = 'Photo storage writable';

    public const LABEL_MAX_SIZE = 'Max photo size per trooper upload';

    public const LABEL_PER_REQUEST = 'Photos per upload request';

    public const LABEL_MEMORY = 'Memory for processing large photos';

    private const UPLOAD_DIRECTORY = 'uploads/events';

    // GD holds the decoded photo plus two square canvases (original + thumbnail clone) at
    // 4 bytes per pixel; for a 4:3 photo that peaks around 15 bytes per photo pixel.
    private const GD_BYTES_PER_PIXEL = 15;

    // Full-resolution shots from current phone main cameras are ~48 megapixels.
    private const LARGE_PHOTO_MEGAPIXELS = 48;

    public function __construct(private readonly PhpIniReader $ini) {}

    /**
     * @return Collection<int, SystemCheckResult>
     */
    public function run(): Collection
    {
        return collect([
            $this->checkFileUploadsEnabled(),
            $this->checkTempDirectory(),
            $this->checkStorageWritable(),
            $this->checkMaxPhotoSize(),
            $this->checkPhotosPerRequest(),
            $this->checkMemory(),
        ]);
    }

    private function checkFileUploadsEnabled(): SystemCheckResult
    {
        $enabled = filter_var($this->ini->get('file_uploads'), FILTER_VALIDATE_BOOLEAN);

        return new SystemCheckResult(
            self::LABEL_FILE_UPLOADS,
            $enabled ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $enabled ? null : 'Set `file_uploads = On` in php.ini; troopers cannot upload photos',
        );
    }

    private function checkTempDirectory(): SystemCheckResult
    {
        $directory = $this->ini->get('upload_tmp_dir');
        $directory = $directory !== '' ? $directory : sys_get_temp_dir();
        $writable = is_dir($directory) && is_writable($directory);

        return new SystemCheckResult(
            self::LABEL_TEMP_DIR,
            $writable ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $writable ? $directory : "{$directory} is missing or not writable by PHP",
        );
    }

    private function checkStorageWritable(): SystemCheckResult
    {
        $disk = Storage::disk('public');
        $probe = self::UPLOAD_DIRECTORY.'/.system-check-'.uniqid();

        try
        {
            $written = $disk->put($probe, 'ok') && $disk->get($probe) === 'ok';
            $disk->delete($probe);
        }
        catch (Throwable $exception)
        {
            return new SystemCheckResult(self::LABEL_STORAGE, SystemCheckStatus::FAIL, $exception->getMessage());
        }

        $path = $disk->path(self::UPLOAD_DIRECTORY);

        return new SystemCheckResult(
            self::LABEL_STORAGE,
            $written ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $written ? $path : "Could not write a test file to {$path}",
        );
    }

    private function checkMaxPhotoSize(): SystemCheckResult
    {
        $app_limit = $this->appLimitBytes();
        $upload_limit = $this->ini->bytes('upload_max_filesize');
        $post_limit = $this->ini->bytes('post_max_size');
        $effective = $this->effectivePhotoBytes();
        $large_photo = (int) config('tracker.image.large_photo_kb', 51200) * 1024;

        $detail = sprintf(
            'Troopers can upload photos up to %s each (app limit %s, upload_max_filesize %s, '
            .'post_max_size %s). The web server body limit (e.g. nginx client_max_body_size) '
            .'must also be at least post_max_size; PHP cannot detect it.',
            $this->formatBytes($effective),
            $this->formatBytes($app_limit),
            $this->formatLimit($upload_limit),
            $this->formatLimit($post_limit),
        );

        if ($effective < $app_limit)
        {
            return new SystemCheckResult(self::LABEL_MAX_SIZE, SystemCheckStatus::FAIL, $detail
                .' PHP rejects photos the app would accept, so troopers see a generic upload error.');
        }

        if ($effective < $large_photo)
        {
            return new SystemCheckResult(self::LABEL_MAX_SIZE, SystemCheckStatus::WARN, $detail
                .' Below the '.$this->formatBytes($large_photo).' recommended for very large photos '
                .'(raise TRACKER_IMAGE_MAX_UPLOAD_KB and the PHP limits).');
        }

        return new SystemCheckResult(self::LABEL_MAX_SIZE, SystemCheckStatus::PASS, $detail);
    }

    private function checkPhotosPerRequest(): SystemCheckResult
    {
        $post_limit = $this->ini->bytes('post_max_size');
        $max_files = (int) $this->ini->get('max_file_uploads');
        $effective = max(1, $this->effectivePhotoBytes());
        $by_size = $post_limit <= 0 ? $max_files : min($max_files, intdiv($post_limit, $effective));

        $detail = "Up to {$by_size} full-size photo(s) per upload (max_file_uploads {$max_files}, "
            .'post_max_size '.$this->formatLimit($post_limit).')';

        if ($by_size < 2)
        {
            return new SystemCheckResult(self::LABEL_PER_REQUEST, SystemCheckStatus::WARN, $detail
                .'. Raise post_max_size well above upload_max_filesize so several large photos fit.');
        }

        return new SystemCheckResult(self::LABEL_PER_REQUEST, SystemCheckStatus::PASS, $detail);
    }

    private function checkMemory(): SystemCheckResult
    {
        $memory_limit = $this->ini->bytes('memory_limit');
        $limit_label = 'memory_limit '.$this->formatLimit($memory_limit);

        if (str_contains((string) config('tracker.image.driver'), 'Imagick'))
        {
            return new SystemCheckResult(self::LABEL_MEMORY, SystemCheckStatus::PASS,
                "Imagick driver; ImageMagick manages photo memory outside PHP's {$limit_label}");
        }

        if ($memory_limit <= 0)
        {
            return new SystemCheckResult(self::LABEL_MEMORY, SystemCheckStatus::PASS, "GD driver, {$limit_label}");
        }

        $megapixels = intdiv($memory_limit, self::GD_BYTES_PER_PIXEL * 1_000_000);
        $detail = "GD driver, {$limit_label}: photos up to about {$megapixels} megapixels can be processed";

        if ($megapixels < self::LARGE_PHOTO_MEGAPIXELS)
        {
            return new SystemCheckResult(self::LABEL_MEMORY, SystemCheckStatus::WARN, $detail
                .'. Raise memory_limit or switch to the Imagick driver for '
                .self::LARGE_PHOTO_MEGAPIXELS.' MP phone photos.');
        }

        return new SystemCheckResult(self::LABEL_MEMORY, SystemCheckStatus::PASS, $detail);
    }

    private function appLimitBytes(): int
    {
        return (int) config('tracker.image.max_upload_kb', 51200) * 1024;
    }

    private function effectivePhotoBytes(): int
    {
        $limits = array_filter([
            $this->appLimitBytes(),
            $this->ini->bytes('upload_max_filesize'),
            $this->ini->bytes('post_max_size'),
        ], fn (int $limit): bool => $limit > 0);

        return $limits === [] ? 0 : min($limits);
    }

    private function formatLimit(int $bytes): string
    {
        return $bytes <= 0 ? 'unlimited' : $this->formatBytes($bytes);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 ** 3)
        {
            return $this->trimNumber($bytes / 1024 ** 3).' GB';
        }

        if ($bytes >= 1024 ** 2)
        {
            return $this->trimNumber($bytes / 1024 ** 2).' MB';
        }

        return $this->trimNumber($bytes / 1024).' KB';
    }

    private function trimNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
