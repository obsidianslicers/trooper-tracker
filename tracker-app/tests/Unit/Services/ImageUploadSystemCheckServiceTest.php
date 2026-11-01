<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\SystemCheckStatus;
use App\Services\ImageUploadSystemCheckService;
use App\Services\PhpIniReader;
use App\Services\SystemCheckResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Tests\TestCase;

class ImageUploadSystemCheckServiceTest extends TestCase
{
    private const HEALTHY_INI = [
        'file_uploads' => '1',
        'upload_tmp_dir' => '',
        'upload_max_filesize' => '30M',
        'post_max_size' => '200M',
        'max_file_uploads' => '20',
        'memory_limit' => '1G',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'tracker.image.driver' => GdDriver::class,
            'tracker.image.max_upload_kb' => 25600,
            'tracker.image.large_photo_kb' => 20480,
        ]);
    }

    public function test_healthy_configuration_passes_every_check(): void
    {
        $results = $this->runChecks();

        $this->assertCount(6, $results);
        $results->each(fn (SystemCheckResult $result) => $this->assertSame(
            SystemCheckStatus::PASS,
            $result->status,
            "{$result->label}: {$result->detail}",
        ));
    }

    public function test_fails_when_php_file_uploads_are_disabled(): void
    {
        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_FILE_UPLOADS, ['file_uploads' => '0']);

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
    }

    public function test_fails_when_upload_temp_directory_is_missing(): void
    {
        $result = $this->checkResult(
            ImageUploadSystemCheckService::LABEL_TEMP_DIR,
            ['upload_tmp_dir' => '/path/that/does/not/exist'],
        );

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
    }

    public function test_photo_storage_probe_leaves_no_files_behind(): void
    {
        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_STORAGE);

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_reports_effective_max_photo_size_from_smallest_limit(): void
    {
        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_MAX_SIZE);

        $this->assertStringContainsString('up to 25 MB each', (string) $result->detail);
        $this->assertStringContainsString('upload_max_filesize 30 MB', (string) $result->detail);
    }

    public function test_fails_when_php_limit_is_below_app_limit(): void
    {
        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_MAX_SIZE, ['upload_max_filesize' => '2M']);

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
        $this->assertStringContainsString('up to 2 MB each', (string) $result->detail);
    }

    public function test_warns_when_limits_are_too_low_for_very_large_photos(): void
    {
        config(['tracker.image.max_upload_kb' => 12288]);

        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_MAX_SIZE);

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
        $this->assertStringContainsString('20 MB recommended', (string) $result->detail);
    }

    public function test_treats_zero_php_limits_as_unlimited(): void
    {
        $result = $this->checkResult(
            ImageUploadSystemCheckService::LABEL_MAX_SIZE,
            ['upload_max_filesize' => '0', 'post_max_size' => '0'],
        );

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
        $this->assertStringContainsString('post_max_size unlimited', (string) $result->detail);
    }

    public function test_warns_when_post_max_size_fits_only_one_full_size_photo(): void
    {
        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_PER_REQUEST, ['post_max_size' => '30M']);

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
        $this->assertStringContainsString('Up to 1 full-size photo(s)', (string) $result->detail);
    }

    public function test_warns_when_gd_memory_limit_is_too_low_for_large_photos(): void
    {
        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_MEMORY, ['memory_limit' => '256M']);

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
        $this->assertStringContainsString('about 17 megapixels', (string) $result->detail);
    }

    public function test_passes_memory_check_for_imagick_driver(): void
    {
        config(['tracker.image.driver' => ImagickDriver::class]);

        $result = $this->checkResult(ImageUploadSystemCheckService::LABEL_MEMORY, ['memory_limit' => '128M']);

        $this->assertSame(SystemCheckStatus::PASS, $result->status);
    }

    /**
     * @param  array<string, string>  $ini
     */
    private function checkResult(string $label, array $ini = []): SystemCheckResult
    {
        $result = $this->runChecks($ini)->firstWhere('label', $label);
        $this->assertInstanceOf(SystemCheckResult::class, $result);

        return $result;
    }

    /**
     * @param  array<string, string>  $ini
     * @return Collection<int, SystemCheckResult>
     */
    private function runChecks(array $ini = []): Collection
    {
        $values = array_merge(self::HEALTHY_INI, $ini);
        $reader = new class($values) extends PhpIniReader {
            /**
             * @param  array<string, string>  $values
             */
            public function __construct(private readonly array $values) {}

            public function get(string $key): string
            {
                return $this->values[$key] ?? '';
            }
        };

        $subject = new ImageUploadSystemCheckService($reader);

        return $subject->run();
    }
}
