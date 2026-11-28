<?php

declare(strict_types=1);

namespace App\Messages\System\PageData;

use App\Messages\System\Commands\StampDeployment;
use Hyperdrive\Message;

/**
 * The deployed commit and deploy time. Both are null until a deploy has stamped them.
 *
 * @method static array{commit: ?string, deployed_at: ?string} call()
 */
final class StatusPageData extends Message
{
    public function handle(): array
    {
        $path = storage_path(StampDeployment::FILE);
        $info = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $info = is_array($info) ? $info : [];

        return [
            'commit' => $info['commit'] ?? null,
            'deployed_at' => $info['deployed_at'] ?? null,
        ];
    }
}
