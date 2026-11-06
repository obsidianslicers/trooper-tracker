<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use TroopTracker\Plugins\Holonet\Exceptions\LinkRevokedException;
use TroopTracker\Plugins\Holonet\Exceptions\TrackerUnavailableException;

/**
 * Calls another tracker's linked-tracker endpoints.
 */
class TrackerClient
{
    public function __construct(private readonly ThisTracker $this_tracker) {}

    /**
     * Swap a one-time code for an access key.
     *
     * @return array{token: string, tracker_name: string, display_name: string|null}
     *
     * @throws TrackerUnavailableException
     */
    public function exchangeCode(string $tracker_url, string $code, string $verifier): array
    {
        $response = $this->send(fn (): Response => $this->request()->post(
            $tracker_url.ThisTracker::TOKEN_PATH,
            [
                'code' => $code,
                'code_verifier' => $verifier,
                'client' => $this->this_tracker->url(),
                'client_name' => $this->this_tracker->name(),
            ],
        ));

        if (!$response->successful() || !is_string($response->json('token')))
        {
            throw new TrackerUnavailableException(
                "The tracker didn't accept the link (HTTP {$response->status()})."
            );
        }

        return [
            'token' => $response->json('token'),
            'tracker_name' => (string) ($response->json('tracker.name') ?? ''),
            'display_name' => is_string($response->json('trooper.display_name'))
                ? $response->json('trooper.display_name')
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws LinkRevokedException
     * @throws TrackerUnavailableException
     */
    public function history(string $tracker_url, string $token): array
    {
        $response = $this->send(fn (): Response => $this->request()
            ->withToken($token)
            ->post($tracker_url.ThisTracker::HISTORY_PATH));

        if ($response->status() === 429)
        {
            $this->markBusy($tracker_url, (int) $response->header('Retry-After'));

            throw new TrackerUnavailableException('The tracker asked us to slow down. Trying again later.');
        }

        if ($response->status() === 401)
        {
            throw new LinkRevokedException('The tracker no longer accepts this link.');
        }

        $data = $response->json();

        if (!$response->successful() || !is_array($data) || !is_array($data['troops'] ?? null))
        {
            throw new TrackerUnavailableException(
                "The tracker didn't send troop history (HTTP {$response->status()})."
            );
        }

        return $data;
    }

    /**
     * Tell the other tracker the link was removed. Best effort: the access key is
     * forgotten here either way.
     */
    public function revoke(string $tracker_url, string $token): void
    {
        try
        {
            $this->request()->withToken($token)->post($tracker_url.ThisTracker::REVOKE_PATH);
        }
        catch (ConnectionException)
        {
            // The other tracker refuses the key on its next use once it is gone here.
        }
    }

    /**
     * Whether the tracker asked us to slow down and the wait isn't over.
     */
    public function isBusy(string $tracker_url): bool
    {
        return Cache::has(self::busyKey($tracker_url));
    }

    private function markBusy(string $tracker_url, int $retry_after): void
    {
        $seconds = min(max($retry_after, 60), 86400);

        Cache::put(self::busyKey($tracker_url), true, now()->addSeconds($seconds));
    }

    private static function busyKey(string $tracker_url): string
    {
        return 'holonet:busy:'.sha1($tracker_url);
    }

    private function request(): PendingRequest
    {
        return Http::timeout((int) config('plugins.holonet.timeout', 10))
            ->acceptJson()
            ->asJson()
            ->withUserAgent('TroopTracker linked trackers');
    }

    /**
     * @param  callable(): Response  $call
     */
    private function send(callable $call): Response
    {
        try
        {
            return $call();
        }
        catch (ConnectionException $e)
        {
            throw new TrackerUnavailableException("The tracker couldn't be reached.", 0, $e);
        }
    }
}
