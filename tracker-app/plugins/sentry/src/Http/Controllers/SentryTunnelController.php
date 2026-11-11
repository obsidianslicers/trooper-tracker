<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Sentry\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Sentry\Dsn;
use TroopTracker\Plugins\Sentry\SentrySettings;

/**
 * POST /webhooks/sentry: passes a browser's error report (a Sentry envelope) on to Sentry,
 * so ad blockers that block sentry.io don't hide errors. Only envelopes addressed to the
 * Browser DSN are forwarded, and only to that DSN's own host, so this can't be used to
 * send anything anywhere else.
 *
 * @see https://docs.sentry.io/platforms/javascript/troubleshooting/#using-the-tunnel-option
 */
final class SentryTunnelController
{
    private const MAX_BYTES = 1_048_576;

    private const PER_MINUTE = 60;

    public function __invoke(Request $request, SentrySettings $settings): Response
    {
        $configured = $this->parse($settings->browserDsn());

        if ($configured === null || !$settings->tunnel())
        {
            return response('', 404);
        }

        $limit_key = 'sentry-tunnel:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limit_key, self::PER_MINUTE))
        {
            return response('', 429);
        }

        RateLimiter::hit($limit_key, 60);

        $envelope = $request->getContent();

        if ($envelope === '')
        {
            return response('', 400);
        }

        if (strlen($envelope) > self::MAX_BYTES)
        {
            return response('', 413);
        }

        if (!$this->isAddressedTo($envelope, $configured))
        {
            return response('', 403);
        }

        try
        {
            $forwarded = Http::withBody($envelope, 'application/x-sentry-envelope')
                ->timeout(10)
                ->post($configured->getEnvelopeApiEndpointUrl());
        }
        catch (ConnectionException)
        {
            return response('', 502);
        }

        return response($forwarded->body(), $forwarded->status());
    }

    /**
     * The envelope's first line is a JSON header naming the DSN it is for.
     */
    private function isAddressedTo(string $envelope, Dsn $configured): bool
    {
        $header = json_decode(strtok($envelope, "\n") ?: '', true);
        $dsn = $this->parse(is_array($header) ? ($header['dsn'] ?? null) : null);

        return $dsn !== null
            && $dsn->getHost() === $configured->getHost()
            && $dsn->getProjectId() === $configured->getProjectId()
            && $dsn->getPublicKey() === $configured->getPublicKey();
    }

    private function parse(mixed $dsn): ?Dsn
    {
        if (!is_string($dsn) || $dsn === '')
        {
            return null;
        }

        try
        {
            return Dsn::createFromString($dsn);
        }
        catch (InvalidArgumentException)
        {
            return null;
        }
    }
}
