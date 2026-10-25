<?php

declare(strict_types=1);

namespace App\Services\SystemChecks;

use App\Enums\SystemCheckStatus;
use App\Facades\TroopTracker;
use App\Services\Forums\XenforoService;
use App\Services\SystemCheckResult;
use Illuminate\Support\Collection;

class XenforoSystemCheck
{
    private const MINIMUM_VERSION_ID = 2020000;

    private const REQUIRED_SCOPES = [
        'user:read', 'user:write', 'thread:read', 'thread:write', 'post:read', 'post:write',
        'upgrades:read', 'usergroups:read', 'system:read',
    ];

    private const REQUIRED_EXTENSIONS = ['mysqli', 'mbstring', 'gd', 'curl', 'openssl', 'xml'];

    private const ALWAYS_WRITABLE_PATHS = ['internal_data', 'data'];

    public function __construct(
        private readonly TroopTracker $troop_tracker,
        private readonly XenforoService $xenforo,
        private readonly PhpIniCheck $php_ini_check,
        private readonly DiskSpaceCheck $disk_space_check,
        private readonly SslCertificateInspector $ssl_inspector,
    ) {}

    public function isConfigured(): bool
    {
        return $this->troop_tracker->isXenforoOAuthConfigured()
            || $this->troop_tracker->isXenforoIntegrationConfigured();
    }

    /**
     * @return Collection<int, SystemCheckResult>
     */
    public function run(): Collection
    {
        $results = $this->checkConfiguration();

        if (!$this->troop_tracker->isXenforoIntegrationConfigured())
        {
            return $results;
        }

        $results->push($this->ssl_inspector->check('Forum SSL certificate', (string) config('services.xenforo.base_url')));

        return $results->merge($this->checkApi())->merge($this->checkForumHealth());
    }

    /**
     * @return Collection<int, SystemCheckResult>
     */
    private function checkConfiguration(): Collection
    {
        return collect([
            $this->toResult('XenForo OAuth', $this->troop_tracker->isXenforoOAuthConfigured()),
            $this->toResult('XenForo API key and base URL', $this->troop_tracker->isXenforoIntegrationConfigured()),
            $this->toResult('XENFORO_API_USER set', !empty(config('services.xenforo.api_user'))),
            $this->toResult('XENFORO_WEBHOOK_SECRET set', !empty(config('services.xenforo.webhook_secret'))),
        ]);
    }

    /**
     * @return Collection<int, SystemCheckResult>
     */
    private function checkApi(): Collection
    {
        $index = $this->xenforo->getApiIndex();

        if ($index === null)
        {
            return collect([new SystemCheckResult(
                'XenForo API reachable',
                SystemCheckStatus::FAIL,
                'API index request failed; check XENFORO_BASE_URL and that XENFORO_API_KEY is active',
            )]);
        }

        return collect([
            new SystemCheckResult('XenForo API reachable', SystemCheckStatus::PASS),
            $this->checkVersion((int) ($index['version_id'] ?? 0)),
            $this->checkScopes((array) ($index['key'] ?? [])),
            $this->toResult('Add-on: upgrade-stats endpoint', $this->xenforo->get_upgrade_stats() !== null),
            $this->toResult('Add-on: user-groups list (1.2.0+)', $this->xenforo->getAllUserGroups() !== null),
        ]);
    }

    private function checkVersion(int $version_id): SystemCheckResult
    {
        $passes = $version_id >= self::MINIMUM_VERSION_ID;

        return new SystemCheckResult(
            'XenForo version',
            $passes ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            "Version ID {$version_id}, requires XenForo 2.2.0+",
        );
    }

    /**
     * @param  array<string, mixed>  $key
     */
    private function checkScopes(array $key): SystemCheckResult
    {
        if (!empty($key['allow_all_scopes']))
        {
            return new SystemCheckResult('API key scopes', SystemCheckStatus::PASS, 'Key allows all scopes');
        }

        $missing = array_diff(self::REQUIRED_SCOPES, (array) ($key['scopes'] ?? []));

        return new SystemCheckResult(
            'API key scopes',
            $missing === [] ? SystemCheckStatus::PASS : SystemCheckStatus::WARN,
            $missing === [] ? null : 'Missing: '.implode(', ', $missing),
        );
    }

    /**
     * @return Collection<int, SystemCheckResult>
     */
    private function checkForumHealth(): Collection
    {
        $health = $this->xenforo->getSystemHealth();

        if ($health === null)
        {
            return collect([new SystemCheckResult(
                'Add-on: system-health endpoint (1.3.0+)',
                SystemCheckStatus::WARN,
                'Update the Troop Tracker add-on to 1.3.0+ and allow the system:read scope',
            )]);
        }

        $php = (array) ($health['php'] ?? []);
        $server = (array) ($health['server'] ?? []);

        return collect([
            new SystemCheckResult('Add-on: system-health endpoint (1.3.0+)', SystemCheckStatus::PASS),
            $this->checkPendingUpgrades((array) ($health['xenforo']['pending_upgrades'] ?? [])),
            $this->checkForumExtensions((array) ($php['extensions'] ?? []), (string) ($php['version'] ?? '?')),
            $this->php_ini_check->check((array) ($php['ini'] ?? []), 'Forum php.ini limits'),
            ...$this->checkPaths((array) ($health['paths'] ?? [])),
            $this->checkImageProcessing((array) ($health['attachments'] ?? []), $php),
            ...$this->checkPayments((array) ($health['payments'] ?? [])),
            $this->disk_space_check->check('Forum disk space', $server['disk_free'] ?? null, $server['disk_total'] ?? null),
        ]);
    }

    /**
     * @param  array<int, string>  $pending
     */
    private function checkPendingUpgrades(array $pending): SystemCheckResult
    {
        return new SystemCheckResult(
            'XenForo / add-on upgrades applied',
            $pending === [] ? SystemCheckStatus::PASS : SystemCheckStatus::FAIL,
            $pending === [] ? null : 'Pending: '.implode(', ', $pending).' (missing DB columns until upgraded)',
        );
    }

    /**
     * @param  array<string, bool>  $extensions
     */
    private function checkForumExtensions(array $extensions, string $php_version): SystemCheckResult
    {
        $missing_required = array_filter(self::REQUIRED_EXTENSIONS, fn (string $ext): bool => empty($extensions[$ext]));
        $missing_optional = array_keys(array_filter(
            $extensions,
            fn (bool $loaded, string $ext): bool => !$loaded && !in_array($ext, self::REQUIRED_EXTENSIONS, true),
            ARRAY_FILTER_USE_BOTH,
        ));

        $status = match (true)
        {
            $missing_required !== [] => SystemCheckStatus::FAIL,
            $missing_optional !== [] => SystemCheckStatus::WARN,
            default => SystemCheckStatus::PASS,
        };
        $missing = array_merge($missing_required, $missing_optional);
        $detail = "PHP {$php_version}".($missing === [] ? '' : '; missing: '.implode(', ', $missing));

        return new SystemCheckResult('Forum PHP extensions', $status, $detail);
    }

    /**
     * @param  array<string, array<string, mixed>>  $paths
     * @return array<int, SystemCheckResult>
     */
    private function checkPaths(array $paths): array
    {
        $unwritable_data = [];
        $unwritable_addon = [];
        $world_writable = [];

        foreach ($paths as $path => $info)
        {
            $is_data = in_array(explode('/', $path)[0], self::ALWAYS_WRITABLE_PATHS, true);

            if (empty($info['writable']) && $is_data)
            {
                $unwritable_data[] = $path;
            }
            elseif (empty($info['writable']))
            {
                $unwritable_addon[] = $path;
            }

            if (!empty($info['world_writable']))
            {
                $world_writable[] = $path;
            }
        }

        return [
            $this->toListResult('Forum internal_data/ and data/ writable', $unwritable_data, SystemCheckStatus::FAIL, 'Not writable'),
            $this->toListResult('Forum js/, src/addons/, styles/ writable', $unwritable_addon, SystemCheckStatus::WARN, 'Not writable (add-on installs will fail)'),
            $this->toListResult('Forum has no 777 directories', $world_writable, SystemCheckStatus::WARN, 'World-writable'),
        ];
    }

    /**
     * @param  array<string, mixed>  $attachments
     * @param  array<string, mixed>  $php
     */
    private function checkImageProcessing(array $attachments, array $php): SystemCheckResult
    {
        $library = (string) ($attachments['image_library'] ?? 'gd');
        $extension = $library === 'imPecl' ? 'imagick' : 'gd';
        $loaded = !empty($php['extensions'][$extension]);
        $max_kb = (int) ($attachments['max_file_size_kb'] ?? 0);
        $upload_max = IniValue::toNumber((string) ($php['ini']['upload_max_filesize'] ?? ''));
        $exceeds_upload = $upload_max !== null && $max_kb * 1024 > $upload_max;

        $detail = "Image library {$library} (ext-{$extension}), attachment limit {$max_kb} KB";

        if (!$loaded)
        {
            return new SystemCheckResult('Forum attachments & images', SystemCheckStatus::FAIL, "{$detail}; ext-{$extension} missing");
        }

        $status = $exceeds_upload ? SystemCheckStatus::WARN : SystemCheckStatus::PASS;
        $suffix = $exceeds_upload ? '; larger than upload_max_filesize' : '';

        return new SystemCheckResult('Forum attachments & images', $status, $detail.$suffix);
    }

    /**
     * @param  array<string, mixed>  $payments
     * @return array<int, SystemCheckResult>
     */
    private function checkPayments(array $payments): array
    {
        $days = (int) ($payments['error_days'] ?? 30);
        $errors = (array) ($payments['errors'] ?? []);
        $error_list = collect($errors)->map(fn (mixed $count, string $message): string => "{$message} ({$count})");
        $provider_data_errors = (int) ($payments['provider_data_errors'] ?? 0);
        $tables = (array) ($payments['provider_data_tables'] ?? []);

        return [
            new SystemCheckResult(
                "Payment errors (last {$days} days)",
                $errors === [] ? SystemCheckStatus::PASS : SystemCheckStatus::WARN,
                $errors === [] ? ((int) ($payments['active_profiles'] ?? 0)).' active payment profile(s)' : $error_list->implode('; '),
            ),
            new SystemCheckResult(
                'provider_data column',
                $provider_data_errors > 0 ? SystemCheckStatus::FAIL : SystemCheckStatus::PASS,
                $provider_data_errors > 0
                    ? "{$provider_data_errors} error(s) mention provider_data; run the XenForo upgrade"
                    : 'Found in: '.($tables === [] ? 'no xf_ tables' : implode(', ', $tables)),
            ),
        ];
    }

    /**
     * @param  array<int, string>  $items
     */
    private function toListResult(string $label, array $items, SystemCheckStatus $failure, string $prefix): SystemCheckResult
    {
        return new SystemCheckResult(
            $label,
            $items === [] ? SystemCheckStatus::PASS : $failure,
            $items === [] ? null : "{$prefix}: ".implode(', ', $items),
        );
    }

    private function toResult(string $label, bool $passes): SystemCheckResult
    {
        return new SystemCheckResult($label, $passes ? SystemCheckStatus::PASS : SystemCheckStatus::WARN);
    }
}
