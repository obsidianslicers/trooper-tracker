<?php

namespace App\Services\Forums;

use App\Enums\OauthProvider;
use App\Helpers\ForumBBCodeRenderer;
use App\Models\OauthLogin;
use App\Support\XenforoUpgradeHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class XenforoService
{
    private string $base_url;

    private ?string $api_key;

    private ?string $api_user;

    public function __construct()
    {
        $this->base_url = rtrim(
            (string) config('services.xenforo.base_url', config('xenforo.base_url', env('XENFORO_BASE_URL', ''))),
            '/'
        );
        $this->api_key = config('services.xenforo.api_key', config('xenforo.api_key', env('XENFORO_API_KEY')));
        $this->api_user = config('services.xenforo.api_user', config('xenforo.api_user', env('XENFORO_API_USER')));
    }

    private function buildApiHeaders(?int $user_id = null): array
    {
        if (empty($this->api_key))
        {
            return [];
        }

        $headers = [
            'XF-Api-Key' => (string) $this->api_key,
        ];

        $effectiveUser = $user_id ?? $this->api_user;

        if (!empty($effectiveUser))
        {
            $headers['XF-Api-User'] = (string) $effectiveUser;
        }

        return $headers;
    }

    /**
     * Fetch recent posts for a XenForo thread.
     *
     * @return array<int, array{post_id:int,username:string,avatar_url:?string,message:string,message_bbcode:string,message_html:string,post_date:?int,post_url:?string}>
     */
    public function get_thread_posts(
        int $thread_id,
        ?int $exclude_post_id = null,
        int $per_page = 50,
        int $max_pages = 10
    ): array {
        if (empty($this->base_url) || empty($this->api_key))
        {
            return [];
        }

        $url = $this->base_url.'/api/threads/'.$thread_id.'/posts';

        $headers = [
            'XF-Api-Key' => (string) $this->api_key,
        ];

        if (!empty($this->api_user))
        {
            $headers['XF-Api-User'] = (string) $this->api_user;
        }

        $normalized = [];
        $page = 1;
        $max_pages = max(1, $max_pages);
        $per_page = max(1, $per_page);

        while ($page <= $max_pages)
        {
            try
            {
                $response = Http::withHeaders($headers)
                    ->acceptJson()
                    ->timeout(5)
                    ->get($url, [
                        'page' => $page,
                        'per_page' => $per_page,
                    ]);
            }
            catch (\Throwable $e)
            {
                Log::warning('Failed to fetch XenForo thread posts', [
                    'thread_id' => $thread_id,
                    'url' => $url,
                    'page' => $page,
                    'message' => $e->getMessage(),
                ]);

                break;
            }

            if (!$response->successful())
            {
                break;
            }

            $data = $response->json();
            $posts = $data['posts'] ?? [];
            if (!is_array($posts))
            {
                break;
            }

            foreach ($posts as $post)
            {
                if (!is_array($post) || empty($post['post_id']))
                {
                    continue;
                }

                $user = null;
                if (isset($post['User']) && is_array($post['User']))
                {
                    $user = $post['User'];
                }
                elseif (isset($post['user']) && is_array($post['user']))
                {
                    $user = $post['user'];
                }

                $username = (string) (
                    $user['username'] ??
                    $post['username'] ??
                    $post['user_username'] ??
                    'Unknown'
                );

                $avatar_url = null;
                if (is_array($user))
                {
                    $avatar_url = $user['avatar_urls']['s']
                        ?? $user['avatar_urls']['m']
                        ?? $user['avatar_url']
                        ?? null;
                }

                if (is_string($avatar_url) && $avatar_url !== '' && !Str::startsWith($avatar_url, ['http://', 'https://']))
                {
                    $avatar_url = $this->base_url.'/'.ltrim($avatar_url, '/');
                }

                $raw_message = (string) ($post['message'] ?? $post['message_text'] ?? '');
                $message_bbcode = trim($raw_message);

                // Fallback preview for list rendering/search.
                $message_plain = trim(preg_replace('/\s+/', ' ', preg_replace('~\[[^\]]+\]~', '', $message_bbcode) ?? '') ?? '');
                $message_html = ForumBBCodeRenderer::toHtml($message_bbcode);

                $post_id = (int) $post['post_id'];
                $post_url = !empty($this->base_url) ? $this->base_url.'/posts/'.$post_id.'/' : null;

                $normalized[] = [
                    'post_id' => $post_id,
                    'username' => $username,
                    'avatar_url' => is_string($avatar_url) ? $avatar_url : null,
                    'message' => $message_plain,
                    'message_bbcode' => $message_bbcode,
                    'message_html' => $message_html,
                    'post_date' => isset($post['post_date']) ? (int) $post['post_date'] : null,
                    'post_url' => $post_url,
                ];
            }

            $lastPage = null;
            if (isset($data['pagination']) && is_array($data['pagination']))
            {
                $lastPage = isset($data['pagination']['last_page']) ? (int) $data['pagination']['last_page'] : null;
            }

            if ($lastPage !== null && $page >= $lastPage)
            {
                break;
            }

            if (count($posts) < $per_page)
            {
                break;
            }

            $page++;
        }

        if ($exclude_post_id !== null)
        {
            $normalized = array_values(array_filter(
                $normalized,
                static fn (array $post): bool => (int) ($post['post_id'] ?? 0) !== $exclude_post_id
            ));
        }

        usort($normalized, static function (array $a, array $b): int {
            $dateA = (int) ($a['post_date'] ?? 0);
            $dateB = (int) ($b['post_date'] ?? 0);

            if ($dateA === $dateB)
            {
                return (int) ($b['post_id'] ?? 0) <=> (int) ($a['post_id'] ?? 0);
            }

            return $dateB <=> $dateA;
        });

        return $normalized;
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
        if ($user_id === null)
        {
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
        $headers = $this->buildApiHeaders($user_id);
        $response = Http::withHeaders($headers)->asForm()->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    /**
     * Move an existing XenForo thread to a different node (forum).
     *
     * @return array{status:int,body:mixed}
     */
    public function move_thread(int $thread_id, int $target_node_id): array
    {
        if (empty($this->base_url) || empty($this->api_key) || $thread_id <= 0 || $target_node_id <= 0)
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        $url = $this->base_url.'/api/threads/'.$thread_id.'/move';

        $payload = [
            'target_node_id' => $target_node_id,
            'api_bypass_permissions' => 1,
        ];

        // Use the configured API user as the acting user; the thread
        // being moved is identified by the URL.
        $headers = $this->buildApiHeaders();

        $response = Http::withHeaders($headers)
            ->asForm()
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    /**
     * Update an existing XenForo thread title.
     *
     * @return array{status:int,body:mixed}
     */
    public function update_thread(
        int $thread_id,
        string $title
    ): array {
        if (empty($this->base_url) || empty($this->api_key))
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        if ($thread_id <= 0)
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        $url = $this->base_url.'/api/threads/'.$thread_id;

        $payload = [
            'title' => $title,
            'api_bypass_permissions' => 1,
        ];

        $headers = $this->buildApiHeaders();

        $response = Http::withHeaders($headers)->asForm()->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    /**
     * Update an existing XenForo post's message body.
     *
     * @return array{status:int,body:mixed}
     */
    public function update_post(
        int $post_id,
        string $message,
        ?int $user_id = null
    ): array {
        if (empty($this->base_url) || empty($this->api_key))
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        if ($post_id <= 0)
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        if ($user_id === null)
        {
            $user_id = $this->resolve_user_id_for_trooper(Auth::id());
        }

        $url = $this->base_url.'/api/posts/'.$post_id;

        $payload = [
            'message' => $message,
            'api_bypass_permissions' => 1,
        ];

        $headers = $this->buildApiHeaders($user_id);

        $response = Http::withHeaders($headers)->asForm()->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    /**
     * Fetch a XenForo user by ID.
     *
     * @return array{status:int,body:mixed}
     */
    public function get_user(int $user_id): array
    {
        if (empty($this->base_url) || empty($this->api_key) || $user_id <= 0)
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        $url = $this->base_url.'/api/users/'.$user_id;

        // Use the configured API user as the context, not the
        // target XenForo user. The user ID in the URL identifies
        // which account to update.
        $headers = $this->buildApiHeaders();

        $response = Http::withHeaders($headers)
            ->acceptJson()
            ->get($url);

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
        if ($trooper_id === null)
        {
            return null;
        }

        $oauth = OauthLogin::where(OauthLogin::TROOPER_ID, $trooper_id)
            ->where(OauthLogin::PROVIDER, OauthProvider::XENFORO)
            ->first();

        if ($oauth === null || empty($oauth->provider_id))
        {
            return null;
        }

        return (int) $oauth->provider_id;
    }

    /**
     * Update XenForo user custom fields and/or groups.
     *
     * @param  array<string,mixed>  $payload
     * @return array{status:int,body:mixed}
     */
    public function update_user(int $user_id, array $payload): array
    {
        if (empty($this->base_url) || empty($this->api_key) || $user_id <= 0)
        {
            return [
                'status' => 0,
                'body' => null,
            ];
        }

        $url = $this->base_url.'/api/users/'.$user_id;

        $payload['api_bypass_permissions'] = 1;

        // Use the configured API user as the acting user; the
        // user being modified is identified by the URL.
        $headers = $this->buildApiHeaders();

        $response = Http::withHeaders($headers)
            ->asForm()
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body' => $response->json(),
        ];
    }

    /**
     * Fetch upgrade statistics from the XenForo UpgradeStats add-on.
     *
     * This endpoint is only called when XenForo API credentials are
     * configured. If the request fails or returns a non-array payload,
     * null is returned and callers should fall back to local data.
     *
     * @return array<string,mixed>|null
     */
    public function get_upgrade_stats(): ?array
    {
        if (empty($this->base_url) || empty($this->api_key))
        {
            return null;
        }

        $url = $this->base_url.'/index.php?api/upgrade-stats';

        try
        {
            $headers = $this->buildApiHeaders();

            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(5)
                ->get($url);
        }
        catch (\Throwable $e)
        {
            Log::warning('Failed to fetch XenForo upgrade stats', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful())
        {
            Log::warning('Non-success response from XenForo upgrade stats', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();

        Log::info('XenForo upgrade stats response', [
            'url' => $url,
            'data' => $data,
        ]);

        return is_array($data) ? $data : null;
    }

    /**
     * Fetch the full upgrade history for a specific XenForo user.
     *
     * Calls the per-user upgrade-stats endpoint and normalises each donation
     * record to:
     *   - user_upgrade_record_id (int)
     *   - upgrade_title (string)
     *   - start_date (int)   — Unix timestamp
     *   - end_date (int)     — Unix timestamp; 0 = lifetime/no expiry
     *   - is_active (bool)
     *   - cost_amount (float) — per-period subscription price
     *   - total_amount (float) — months covered × per-period price
     *
     * Returns null when credentials are missing or the request fails.
     *
     * @return array<int,array{user_upgrade_record_id:int,upgrade_title:string,start_date:int,end_date:int,is_active:bool,cost_amount:float,total_amount:float}>|null
     */
    public function get_user_upgrade_history(int $user_id): ?array
    {
        if (empty($this->base_url) || empty($this->api_key) || $user_id <= 0)
        {
            return null;
        }

        $data = $this->get_user_upgrade_stats($user_id);

        if ($data === null)
        {
            return null;
        }

        $records = [];

        foreach ($data['donations'] ?? [] as $row)
        {
            if (!is_array($row))
            {
                continue;
            }

            $upgrade_id = (int) ($row['user_upgrade_id'] ?? 0);
            $start_date = (int) ($row['start_date'] ?? 0);
            $end_date = (int) ($row['end_date'] ?? 0);
            $cost_amount = (float) ($row['cost_amount'] ?? 0.0);
            $months_paid = XenforoUpgradeHelper::countMonthsForRecord($start_date, $end_date);

            $records[] = [
                'user_upgrade_record_id' => (int) ($row['user_upgrade_record_id'] ?? 0),
                'upgrade_title' => (string) ($row['title'] ?? 'Upgrade #'.$upgrade_id),
                'start_date' => $start_date,
                'end_date' => $end_date,
                'is_active' => ($row['status'] ?? '') === 'active',
                'cost_amount' => $cost_amount,
                'total_amount' => round($months_paid * $cost_amount, 2),
            ];
        }

        // Most recent first.
        usort($records, static fn (array $a, array $b): int => $b['start_date'] <=> $a['start_date']);

        return $records;
    }

    /**
     * Fetch raw upgrade stats for a single XenForo user from the per-user endpoint.
     *
     * Returns the decoded JSON payload or null on failure.
     *
     * @return array<string,mixed>|null
     */
    private function get_user_upgrade_stats(int $user_id): ?array
    {
        $url = $this->base_url.'/index.php?api/upgrade-stats/user&user_id='.$user_id;

        try
        {
            $response = Http::withHeaders($this->buildApiHeaders())
                ->acceptJson()
                ->timeout(5)
                ->get($url);
        }
        catch (\Throwable $e)
        {
            Log::warning('Failed to fetch XenForo upgrade stats for user', [
                'url' => $url,
                'user_id' => $user_id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful())
        {
            Log::warning('Non-success response from XenForo upgrade stats for user', [
                'url' => $url,
                'user_id' => $user_id,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * Determine whether the given user has at least one currently active upgrade.
     *
     * Thin wrapper around get_user_upgrade_history() used where only the
     * active/inactive boolean is needed.
     */
    public function get_user_purchases(int $user_id): ?array
    {
        $history = $this->get_user_upgrade_history($user_id);

        if ($history === null)
        {
            return null;
        }

        return array_values(array_filter(
            $history,
            static fn (array $row): bool => $row['is_active']
        ));
    }

    /**
     * Fetch user-group banner data from the XenForo user-groups add-on.
     *
     * @return array<string,mixed>|null
     */
    public function get_user_groups(int $user_id): ?array
    {
        if (empty($this->base_url) || empty($this->api_key) || $user_id <= 0)
        {
            return null;
        }

        $url = $this->base_url.'/index.php?api/user-groups&user_id='.$user_id;

        try
        {
            $headers = $this->buildApiHeaders();

            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(5)
                ->get($url);
        }
        catch (\Throwable $e)
        {
            Log::warning('Failed to fetch XenForo user groups', [
                'url' => $url,
                'user_id' => $user_id,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful())
        {
            Log::warning('Non-success response from XenForo user groups', [
                'url' => $url,
                'user_id' => $user_id,
                'status' => $response->status(),
            ]);

            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }
}
