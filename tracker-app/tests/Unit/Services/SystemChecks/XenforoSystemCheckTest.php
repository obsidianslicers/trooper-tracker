<?php

declare(strict_types=1);

namespace Tests\Unit\Services\SystemChecks;

use App\Enums\SystemCheckStatus;
use App\Services\SystemCheckResult;
use App\Services\SystemChecks\SslCertificateInspector;
use App\Services\SystemChecks\XenforoSystemCheck;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenforoSystemCheckTest extends TestCase
{
    private const BASE = 'https://forum.test/boards';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xenforo.client_id' => null,
            'services.xenforo.client_secret' => null,
            'services.xenforo.redirect' => null,
            'services.xenforo.base_url' => self::BASE,
            'services.xenforo.api_key' => 'key',
            'services.xenforo.api_user' => '1',
            'services.xenforo.webhook_secret' => 'secret',
        ]);

        $this->app->instance(SslCertificateInspector::class, new class extends SslCertificateInspector {
            protected function expiresAt(string $host, int $port): CarbonImmutable
            {
                return CarbonImmutable::now()->addDays(60);
            }
        });
    }

    public function test_is_not_configured_without_oauth_or_api_settings(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => null]);

        $this->assertFalse(app(XenforoSystemCheck::class)->isConfigured());
    }

    public function test_all_checks_pass_with_a_healthy_forum(): void
    {
        $this->fakeForum($this->healthyPayload());

        $results = $this->runSubject();

        $this->assertSame(SystemCheckStatus::PASS, $this->find($results, 'XenForo API reachable')->status);
        $this->assertSame(SystemCheckStatus::PASS, $this->find($results, 'API key scopes')->status);
        $this->assertSame(SystemCheckStatus::PASS, $this->find($results, 'Forum SSL certificate')->status);
        $this->assertSame(SystemCheckStatus::PASS, $this->find($results, 'Forum php.ini limits')->status);
        $this->assertSame(SystemCheckStatus::PASS, $this->find($results, 'provider_data column')->status);
    }

    public function test_warns_to_update_addon_when_system_health_is_missing(): void
    {
        $this->fakeForum(null);

        $result = $this->find($this->runSubject(), 'Add-on: system-health endpoint (1.3.0+)');

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
    }

    public function test_fails_when_api_is_unreachable(): void
    {
        Http::fake(['*' => Http::response([], 401)]);

        $result = $this->find($this->runSubject(), 'XenForo API reachable');

        $this->assertSame(SystemCheckStatus::FAIL, $result->status);
    }

    public function test_reports_payment_errors_permissions_and_pending_upgrades(): void
    {
        $payload = $this->healthyPayload();
        $payload['payments']['errors'] = ['Request not validated' => 3];
        $payload['payments']['provider_data_errors'] = 2;
        $payload['paths']['internal_data']['writable'] = false;
        $payload['paths']['styles']['world_writable'] = true;
        $payload['xenforo']['pending_upgrades'] = ['XF'];
        $payload['php']['extensions']['mbstring'] = false;
        $payload['php']['ini']['upload_max_filesize'] = '2M';
        $this->fakeForum($payload);

        $results = $this->runSubject();

        $this->assertSame(SystemCheckStatus::WARN, $this->find($results, 'Payment errors (last 30 days)')->status);
        $this->assertSame(SystemCheckStatus::FAIL, $this->find($results, 'provider_data column')->status);
        $this->assertSame(SystemCheckStatus::FAIL, $this->find($results, 'Forum internal_data/ and data/ writable')->status);
        $this->assertSame(SystemCheckStatus::WARN, $this->find($results, 'Forum has no 777 directories')->status);
        $this->assertSame(SystemCheckStatus::FAIL, $this->find($results, 'XenForo / add-on upgrades applied')->status);
        $this->assertSame(SystemCheckStatus::FAIL, $this->find($results, 'Forum PHP extensions')->status);
        $this->assertSame(SystemCheckStatus::WARN, $this->find($results, 'Forum php.ini limits')->status);
    }

    public function test_warns_on_missing_api_key_scopes(): void
    {
        $this->fakeForum($this->healthyPayload(), ['allow_all_scopes' => false, 'scopes' => ['user:read']]);

        $result = $this->find($this->runSubject(), 'API key scopes');

        $this->assertSame(SystemCheckStatus::WARN, $result->status);
        $this->assertStringContainsString('system:read', (string) $result->detail);
    }

    /**
     * @return Collection<int, SystemCheckResult>
     */
    private function runSubject(): Collection
    {
        $subject = app(XenforoSystemCheck::class);

        return $subject->run();
    }

    /**
     * @param  Collection<int, SystemCheckResult>  $results
     */
    private function find(Collection $results, string $label): SystemCheckResult
    {
        $result = $results->first(fn (SystemCheckResult $result): bool => $result->label === $label);
        $this->assertNotNull($result, "Missing check: {$label}");

        return $result;
    }

    /**
     * @param  array<string, mixed>|null  $health
     * @param  array<string, mixed>  $key
     */
    private function fakeForum(?array $health, array $key = ['allow_all_scopes' => true]): void
    {
        Http::fake([
            self::BASE.'/index.php?api/system-health' => $health === null
                ? Http::response(['errors' => []], 404)
                : Http::response($health),
            self::BASE.'/index.php?api/upgrade-stats' => Http::response(['userUpgrades' => []]),
            self::BASE.'/index.php?api/user-groups' => Http::response(['userGroups' => []]),
            self::BASE.'/index.php?api/' => Http::response(['version_id' => 2030770, 'key' => $key]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function healthyPayload(): array
    {
        $path = ['exists' => true, 'writable' => true, 'perms' => '0755', 'world_writable' => false];

        return [
            'xenforo' => ['version_id' => 2030770, 'addon_version_id' => 1030000, 'pending_upgrades' => []],
            'php' => [
                'version' => '8.5.0',
                'extensions' => [
                    'mysqli' => true, 'mbstring' => true, 'gd' => true, 'imagick' => true, 'curl' => true,
                    'openssl' => true, 'xml' => true, 'zip' => true, 'intl' => true, 'exif' => true,
                ],
                'ini' => [
                    'upload_max_filesize' => '100M', 'post_max_size' => '256M', 'memory_limit' => '512M',
                    'max_execution_time' => '300', 'max_input_time' => '300', 'max_file_uploads' => '30',
                ],
            ],
            'server' => ['disk_free' => 50 * 1024 ** 3, 'disk_total' => 80 * 1024 ** 3],
            'paths' => [
                'internal_data' => $path, 'data' => $path, 'js' => $path, 'src/addons' => $path, 'styles' => $path,
            ],
            'attachments' => ['max_file_size_kb' => 10240, 'image_library' => 'imPecl'],
            'payments' => [
                'active_profiles' => 1,
                'error_days' => 30,
                'errors' => [],
                'provider_data_tables' => ['xf_purchase_request'],
                'provider_data_errors' => 0,
            ],
        ];
    }
}
