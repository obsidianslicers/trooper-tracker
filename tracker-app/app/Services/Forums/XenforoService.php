<?php

namespace App\Services\Forums;

use App\Models\OauthLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class XenforoService
{
    private string $base_url;

    private ?string $api_key;

    private ?string $api_user;

    public function __construct()
    {
        $this->base_url = rtrim(config('xenforo.base_url', env('XENFORO_BASE_URL', '')), '/');
        $this->api_key = config('xenforo.api_key', env('XENFORO_API_KEY'));
        $this->api_user = config('xenforo.api_user', env('XENFORO_API_USER'));
    }

    public function create_thread(
        int $node_id,
        string $title,
        string $message,
        ?int $user_id = null,
        ?int $prefix_id = null,
        array $extra_fields = []
    ): array {
        // If no explicit XenForo user ID was provided, attempt to resolve it
        // from the currently authenticated trooper's linked XenForo account.
        if ($user_id === null) {
            $user_id = $this->resolve_user_id_for_trooper(Auth::id());
        }

        $url = $this->base_url.'/api/threads';
        $payload = [
            'node_id' => $node_id,
            'title' => $title,
            'message' => $message,
            'api_bypass_permissions' => 1,
        ];
        if ($prefix_id !== null)
        {
            $payload['prefix_id'] = $prefix_id;
        }
        if (!empty($extra_fields))
        {
            $payload = array_merge($payload, $extra_fields);
        }
        $headers = [
            'XF-Api-Key' => (string) $this->api_key,
            'XF-Api-User' => (string) ($user_id ?? $this->api_user ?? ''),
        ];
        $response = Http::withHeaders($headers)->asForm()->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    /**
     * Resolve a XenForo user ID for a given trooper via OAuth mapping.
     * Returns null if no XenForo OAuth login is linked.
     */
    public function resolve_user_id_for_trooper(?int $trooper_id): ?int
    {
        if ($trooper_id === null) {
            return null;
        }

        $oauth = OauthLogin::where(OauthLogin::TROOPER_ID, $trooper_id)
            ->where(OauthLogin::PROVIDER, 'xenforo')
            ->first();

        if ($oauth === null || empty($oauth->provider_id)) {
            return null;
        }

        return (int) $oauth->provider_id;
    }
}
