<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Directory\DirectoryTracker;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;

/**
 * A request from another tracker to link a trooper's account here. It is only
 * valid for a tracker on the list, sending the trooper back to that tracker.
 */
final readonly class LinkRequest
{
    private function __construct(
        public DirectoryTracker $tracker,
        public string $state,
        public string $code_challenge,
    ) {}

    public static function from(Request $request, TrackerDirectory $directory): ?self
    {
        $client = $request->input('client');
        $redirect_uri = $request->input('redirect_uri');
        $state = $request->input('state');
        $challenge = $request->input('code_challenge');

        if (!is_string($client) || !is_string($redirect_uri) || !is_string($state)
            || !is_string($challenge) || $state === '' || strlen($state) > 128
            || preg_match('/^[A-Za-z0-9_-]{43,128}$/', $challenge) !== 1)
        {
            return null;
        }

        $tracker = $directory->others()->get(TrackerDirectory::normalizeUrl($client));

        if ($tracker === null || $redirect_uri !== ThisTracker::callbackUrlFor($tracker->url))
        {
            return null;
        }

        return new self($tracker, $state, $challenge);
    }

    /**
     * @param  array<string, string>  $parameters
     */
    public function returnUrl(array $parameters): string
    {
        return ThisTracker::callbackUrlFor($this->tracker->url).'?'.http_build_query(
            $parameters + ['state' => $this->state]
        );
    }

    /**
     * @return array<string, string>
     */
    public function parameters(): array
    {
        return [
            'client' => $this->tracker->url,
            'redirect_uri' => ThisTracker::callbackUrlFor($this->tracker->url),
            'state' => $this->state,
            'code_challenge' => $this->code_challenge,
        ];
    }
}
