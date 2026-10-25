<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemCheckStatus;
use App\Facades\TroopTracker;
use App\Services\SystemChecks\DiskSpaceCheck;
use App\Services\SystemChecks\PhpIniCheck;
use App\Services\SystemChecks\SslCertificateInspector;
use App\Services\SystemChecks\XenforoSystemCheck;
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

    private const PHP_INI_SETTINGS = [
        'upload_max_filesize', 'post_max_size', 'memory_limit',
        'max_execution_time', 'max_input_time', 'max_file_uploads',
    ];

    public function __construct(
        private readonly TroopTracker $troop_tracker,
        private readonly QueueWorkerHeartbeatService $queue_worker_heartbeat,
        private readonly XenforoSystemCheck $xenforo_check,
        private readonly PhpIniCheck $php_ini_check,
        private readonly DiskSpaceCheck $disk_space_check,
        private readonly SslCertificateInspector $ssl_inspector,
    ) {}

    /**
     * @return Collection<string, Collection<int, SystemCheckResult>>
     */
    public function run(): Collection
    {
        $groups = collect([
            'PHP & Extensions' => $this->checkPhp(),
            'Web Server & SSL' => $this->checkWebServer(),
            'Environment' => $this->checkEnvironment(),
            'Database & Cache' => $this->checkDatabaseAndCache(),
            'Background Services' => $this->checkBackgroundServices(),
            'Storage & Assets' => $this->checkStorageAndAssets(),
            'Integrations' => $this->checkIntegrations(),
        ]);

        if ($this->xenforo_check->isConfigured())
        {
            $groups->put('XenForo', $this->xenforo_check->run());
        }

        return $groups;
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
        $results->push($this->php_ini_check->check($this->currentIniValues(), 'php.ini limits ('.PHP_SAPI.')'));

        return $results;
    }

    /**
     * @return array<string, string>
     */
    private function currentIniValues(): array
    {
        return collect(self::PHP_INI_SETTINGS)
            ->mapWithKeys(fn (string $setting): array => [$setting => (string) ini_get($setting)])
            ->all();
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

    private function checkWebServer(): Collection
    {
        $app_url = (string) config('app.url');
        $results = collect();

        if (function_exists('apache_get_modules'))
        {
            $rewrite = in_array('mod_rewrite', apache_get_modules(), true);
            $results->push(new SystemCheckResult(
                'Apache mod_rewrite',
                $rewrite ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
                $rewrite ? null : 'Run `sudo a2enmod rewrite` and restart Apache',
            ));
        }

        if (!str_starts_with($app_url, 'https://') && !$this->isProduction())
        {
            return $results->push(new SystemCheckResult('APP_URL uses HTTPS', SystemCheckStatus::PASS, 'Skipped outside production'));
        }

        return $results->push($this->ssl_inspector->check('SSL certificate (APP_URL)', $app_url));
    }

    private function checkEnvironment(): Collection
    {
        $app_key_status = !empty(config('app.key')) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL;
        $debug_in_prod = $this->isProduction() && config('app.debug') === true;

        return collect([
            new SystemCheckResult('APP_KEY set', $app_key_status),
            new SystemCheckResult(
                'Debug mode',
                $debug_in_prod ? SystemCheckStatus::WARN : SystemCheckStatus::PASS,
                $debug_in_prod ? 'APP_DEBUG is enabled in production' : null,
            ),
            $this->checkLegacyPaths(),
            $this->checkCached('Config cached', app()->configurationIsCached(), 'php artisan config:cache'),
            $this->checkCached('Routes cached', app()->routesAreCached(), 'php artisan route:cache'),
        ]);
    }

    private function checkLegacyPaths(): SystemCheckResult
    {
        $prefix = (string) config('tracker.system_check.legacy_path_prefix', '/home/bitnami');
        $paths = collect([base_path(), storage_path(), config('view.compiled'), config('cache.stores.file.path')])
            ->merge(collect((array) config('logging.channels'))->pluck('path'))
            ->merge(collect((array) config('filesystems.disks'))->pluck('root'));

        $legacy = $paths->filter(fn (mixed $path): bool => is_string($path) && str_starts_with($path, $prefix))
            ->unique()
            ->values();

        return new SystemCheckResult(
            "No {$prefix} paths",
            $legacy->isEmpty() ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $legacy->isEmpty() ? null : 'Found: '.$legacy->implode(', ').' (re-cache config after fixing .env)',
        );
    }

    private function checkCached(string $label, bool $cached, string $command): SystemCheckResult
    {
        if ($cached || !$this->isProduction())
        {
            return new SystemCheckResult($label, SystemCheckStatus::PASS, $cached ? null : 'Not cached (fine outside production)');
        }

        return new SystemCheckResult($label, SystemCheckStatus::WARN, "Run `{$command}` (deploy.sh does this)");
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

    private function checkBackgroundServices(): Collection
    {
        return collect([
            $this->checkSchedulerHeartbeat(),
            $this->checkQueueWorkerHeartbeat(),
        ]);
    }

    private function checkSchedulerHeartbeat(): SystemCheckResult
    {
        $minutes = $this->queue_worker_heartbeat->minutesSinceSchedulerHeartbeat();
        $down_minutes = (int) config('tracker.supervisor_check.heartbeat_down_minutes', 10);

        if ($minutes === null || $minutes >= $down_minutes)
        {
            return new SystemCheckResult(
                'Scheduler (cron)',
                SystemCheckStatus::FAIL,
                ($minutes === null ? 'Never run' : "Last ran {$minutes} minute(s) ago")
                    .' - add `* * * * * php artisan schedule:run` to crontab',
            );
        }

        return new SystemCheckResult('Scheduler (cron)', SystemCheckStatus::PASS, "Last ran {$minutes} minute(s) ago");
    }

    private function checkQueueWorkerHeartbeat(): SystemCheckResult
    {
        if ((string) config('queue.default') === 'sync')
        {
            return new SystemCheckResult(
                'Queue worker heartbeat',
                SystemCheckStatus::PASS,
                'Queue driver is sync; jobs run inline, no background worker required',
            );
        }

        $minutes_since_last_heartbeat = $this->queue_worker_heartbeat->minutesSinceLastHeartbeat();

        if ($minutes_since_last_heartbeat === null)
        {
            return new SystemCheckResult(
                'Queue worker heartbeat',
                SystemCheckStatus::FAIL,
                'No heartbeat recorded yet - check `sudo supervisorctl status laravel-worker` and the scheduler',
            );
        }

        $warn_minutes = (int) config('tracker.supervisor_check.heartbeat_warn_minutes', 3);
        $down_minutes = (int) config('tracker.supervisor_check.heartbeat_down_minutes', 10);
        $detail = "Last seen {$minutes_since_last_heartbeat} minute(s) ago";

        if ($minutes_since_last_heartbeat >= $down_minutes)
        {
            return new SystemCheckResult('Queue worker heartbeat', SystemCheckStatus::FAIL, $detail);
        }

        if ($minutes_since_last_heartbeat >= $warn_minutes)
        {
            return new SystemCheckResult('Queue worker heartbeat', SystemCheckStatus::WARN, $detail);
        }

        return new SystemCheckResult('Queue worker heartbeat', SystemCheckStatus::PASS, $detail);
    }

    private function checkStorageAndAssets(): Collection
    {
        return collect([
            $this->checkWritable(storage_path(), 'storage/ writable'),
            $this->checkWritable(storage_path('app'), 'storage/app writable'),
            $this->checkWritable(storage_path('logs'), 'storage/logs writable'),
            $this->checkLogFileWritable(),
            $this->checkWritable(base_path('bootstrap/cache'), 'bootstrap/cache writable'),
            $this->disk_space_check->check('Disk space', @disk_free_space(base_path()) ?: null, @disk_total_space(base_path()) ?: null),
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

    private function checkLogFileWritable(): SystemCheckResult
    {
        $log_file = storage_path('logs/laravel.log');

        if (!File::exists($log_file))
        {
            return new SystemCheckResult('laravel.log writable', SystemCheckStatus::PASS, 'Not created yet');
        }

        return $this->checkWritable($log_file, 'laravel.log writable');
    }

    private function checkWritable(string $path, string $label): SystemCheckResult
    {
        return new SystemCheckResult($label, is_writable($path) ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL);
    }

    private function checkIntegrations(): Collection
    {
        $mail_mailer = (string) config('mail.default');
        $mail_warn = $mail_mailer === 'log' && $this->isProduction();

        return collect([
            $this->toResult('Google OAuth', $this->troop_tracker->isGoogleOAuthConfigured()),
            $this->toResult('Google Sync', $this->troop_tracker->isGoogleSyncConfigured()),
            $this->toResult('Google Maps API key', !empty(config('services.google.maps_api_key'))),
            $this->toResult('Discord webhook', $this->troop_tracker->isDiscordIntegrationConfigured()),
            new SystemCheckResult(
                "Mail transport ({$mail_mailer})",
                $mail_warn ? SystemCheckStatus::WARN : SystemCheckStatus::PASS,
                $mail_warn ? 'MAIL_MAILER is "log" in production' : null,
            ),
        ]);
    }

    private function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    private function toResult(string $label, bool $configured): SystemCheckResult
    {
        return new SystemCheckResult($label, $configured ? SystemCheckStatus::PASS : SystemCheckStatus::WARN);
    }
}
