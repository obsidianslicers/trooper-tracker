<?php

declare(strict_types=1);

namespace App\Services\SystemChecks;

use App\Enums\SystemCheckStatus;
use App\Services\SystemCheckResult;
use Carbon\CarbonImmutable;
use RuntimeException;
use Throwable;

class SslCertificateInspector
{
    private const TIMEOUT_SECONDS = 5;

    public function check(string $label, string $url): SystemCheckResult
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || $host === '')
        {
            return new SystemCheckResult($label, SystemCheckStatus::WARN, "{$url} does not use HTTPS");
        }

        try
        {
            $expires_at = $this->expiresAt($host, (int) (parse_url($url, PHP_URL_PORT) ?: 443));
        }
        catch (Throwable $exception)
        {
            return new SystemCheckResult($label, SystemCheckStatus::FAIL, $exception->getMessage());
        }

        return $this->toResult($label, $host, $expires_at);
    }

    protected function expiresAt(string $host, int $port): CarbonImmutable
    {
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'SNI_enabled' => true]]);
        $client = @stream_socket_client(
            "ssl://{$host}:{$port}", $error_code, $error_message, self::TIMEOUT_SECONDS,
            STREAM_CLIENT_CONNECT, $context,
        );

        if ($client === false)
        {
            throw new RuntimeException("Could not open a TLS connection to {$host}: {$error_message}");
        }

        $certificate = stream_context_get_params($client)['options']['ssl']['peer_certificate'] ?? null;
        $parsed = $certificate ? openssl_x509_parse($certificate) : false;
        fclose($client);

        if (!is_array($parsed) || !isset($parsed['validTo_time_t']))
        {
            throw new RuntimeException("Could not read the certificate for {$host}");
        }

        return CarbonImmutable::createFromTimestamp((int) $parsed['validTo_time_t']);
    }

    private function toResult(string $label, string $host, CarbonImmutable $expires_at): SystemCheckResult
    {
        $days = (int) floor(CarbonImmutable::now()->diffInDays($expires_at, false));
        $warn_days = (int) config('tracker.system_check.ssl_warn_days', 14);
        $detail = "{$host} expires {$expires_at->toDateString()} ({$days} days)";

        if ($days < 0)
        {
            return new SystemCheckResult($label, SystemCheckStatus::FAIL, "{$host} expired {$expires_at->toDateString()}");
        }

        if ($days < $warn_days)
        {
            return new SystemCheckResult($label, SystemCheckStatus::WARN, "{$detail}; auto-renewal may be broken");
        }

        return new SystemCheckResult($label, SystemCheckStatus::PASS, $detail);
    }
}
