<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemCheckStatus;
use App\Facades\TroopTracker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Imagick;
use Throwable;

class SystemCheckService
{
    private const REQUIRED_EXTENSIONS = [
        'pdo_mysql', 'mbstring', 'openssl', 'curl', 'zip', 'bcmath', 'intl',
    ];

    public function __construct(private readonly TroopTracker $troop_tracker) {}

    /**
     * @return Collection<string, Collection<int, SystemCheckResult>>
     */
    public function run(): Collection
    {
        return collect([
            'PHP & Extensions' => $this->checkPhp(),
            'Environment' => $this->checkEnvironment(),
            'Database & Cache' => $this->checkDatabaseAndCache(),
            'Storage & Assets' => $this->checkStorageAndAssets(),
            'Integrations' => $this->checkIntegrations(),
        ]);
    }

    private function checkPhp(): Collection
    {
        $results = collect([$this->checkVersionSatisfies(PHP_VERSION, '8.2.0', 'PHP Version')]);

        foreach (self::REQUIRED_EXTENSIONS as $extension)
        {
            $results->push($this->checkExtensionLoaded($extension));
        }

        $results->push($this->checkImageExtension());
        $results->push($this->checkHeicSupport());

        return $results;
    }

    private function checkVersionSatisfies(string $actual, string $minimum, string $label): SystemCheckResult
    {
        $status = version_compare($actual, $minimum, '>=') ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL;

        return new SystemCheckResult($label, $status, "Detected {$actual}, requires >= {$minimum}");
    }

    private function checkExtensionLoaded(string $extension): SystemCheckResult
    {
        $status = extension_loaded($extension) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL;

        return new SystemCheckResult("ext-{$extension}", $status);
    }

    private function checkImageExtension(): SystemCheckResult
    {
        $driver = (string) config('tracker.image.driver');
        $extension = str_contains($driver, 'Imagick') ? 'imagick' : 'gd';

        return $this->checkExtensionLoaded($extension);
    }

    private function checkHeicSupport(): SystemCheckResult
    {
        if (!extension_loaded('imagick'))
        {
            return new SystemCheckResult(
                'HEIC/HEIF upload support',
                SystemCheckStatus::FAIL,
                'Imagick extension is required to convert HEIC/HEIF event photo uploads',
            );
        }

        $formats = array_map('strtoupper', Imagick::queryFormats('HEI*'));
        $supported = in_array('HEIC', $formats, true) || in_array('HEIF', $formats, true);

        return new SystemCheckResult(
            'HEIC/HEIF upload support',
            $supported ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $supported ? null : 'Imagick was not compiled with HEIC/HEIF (libheif) support',
        );
    }

    private function checkEnvironment(): Collection
    {
        $app_key_status = !empty(config('app.key')) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL;
        $debug_in_prod = config('app.env') === 'production' && config('app.debug') === true;

        return collect([
            new SystemCheckResult('APP_KEY set', $app_key_status),
            new SystemCheckResult(
                'Debug mode',
                $debug_in_prod ? SystemCheckStatus::WARN : SystemCheckStatus::PASS,
                $debug_in_prod ? 'APP_DEBUG is enabled in production' : null,
            ),
        ]);
    }

    private function checkDatabaseAndCache(): Collection
    {
        return collect([
            $this->checkDatabaseConnection(),
            $this->checkCacheRoundTrip(),
            $this->checkQueueTable(),
        ]);
    }

    private function checkDatabaseConnection(): SystemCheckResult
    {
        try
        {
            DB::connection()->getPdo();

            return new SystemCheckResult('Database connection', SystemCheckStatus::PASS);
        }
        catch (Throwable $exception)
        {
            return new SystemCheckResult('Database connection', SystemCheckStatus::FAIL, $exception->getMessage());
        }
    }

    private function checkCacheRoundTrip(): SystemCheckResult
    {
        $value = uniqid('system_check_', true);
        Cache::put('system_check', $value, 5);
        $status = Cache::get('system_check') === $value ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL;

        return new SystemCheckResult('Cache store ('.config('cache.default').')', $status);
    }

    private function checkQueueTable(): SystemCheckResult
    {
        $connection = (string) config('queue.default');

        if ($connection !== 'database')
        {
            return new SystemCheckResult("Queue driver ({$connection})", SystemCheckStatus::PASS);
        }

        $status = Schema::hasTable('tt_jobs') ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL;

        return new SystemCheckResult('Queue driver (database)', $status, 'tt_jobs table missing');
    }

    private function checkStorageAndAssets(): Collection
    {
        return collect([
            $this->checkWritable(storage_path(), 'storage/ writable'),
            $this->checkWritable(storage_path('app'), 'storage/app writable'),
            new SystemCheckResult(
                'Public storage symlink',
                File::exists(public_path('storage')) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
                'Run `php artisan storage:link`',
            ),
            new SystemCheckResult(
                'Vite build assets',
                File::exists(public_path('build/manifest.json')) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
                'Run `npm run build`',
            ),
        ]);
    }

    private function checkWritable(string $path, string $label): SystemCheckResult
    {
        return new SystemCheckResult($label, is_writable($path) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL);
    }

    private function checkIntegrations(): Collection
    {
        $mail_mailer = (string) config('mail.default');
        $mail_warn = $mail_mailer === 'log' && config('app.env') === 'production';

        return collect([
            $this->toResult('Google OAuth', $this->troop_tracker->isGoogleOAuthConfigured()),
            $this->toResult('Google Sync', $this->troop_tracker->isGoogleSyncConfigured()),
            $this->toResult('Google Maps API key', !empty(config('services.google.maps_api_key'))),
            $this->toResult('XenForo OAuth', $this->troop_tracker->isXenforoOAuthConfigured()),
            $this->toResult('XenForo integration', $this->troop_tracker->isXenforoIntegrationConfigured()),
            $this->toResult('Discord webhook', $this->troop_tracker->isDiscordIntegrationConfigured()),
            new SystemCheckResult(
                "Mail transport ({$mail_mailer})",
                $mail_warn ? SystemCheckStatus::WARN : SystemCheckStatus::PASS,
                $mail_warn ? 'MAIL_MAILER is "log" in production' : null,
            ),
        ]);
    }

    private function toResult(string $label, bool $configured): SystemCheckResult
    {
        return new SystemCheckResult($label, $configured ? SystemCheckStatus::PASS : SystemCheckStatus::WARN);
    }
}
