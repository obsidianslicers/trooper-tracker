<?php

namespace App\Services\Forums;

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
        $url = $this->base_url . '/api/threads';
        $payload = [
            'node_id' => $node_id,
            'title' => $title,
            'message' => $message,
            'api_bypass_permissions' => 1,
        ];
        if ($prefix_id !== null) {
            $payload['prefix_id'] = $prefix_id;
        }
        if (!empty($extra_fields)) {
            $payload = array_merge($payload, $extra_fields);
        }
        $headers = [
            'XF-Api-Key' => (string)$this->api_key,
            'XF-Api-User' => (string)($user_id ?? $this->api_user ?? ''),
        ];
        $response = Http::withHeaders($headers)->asForm()->post($url, $payload);
        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }
}
