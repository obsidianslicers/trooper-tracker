<?php

declare(strict_types=1);

namespace App\Messages\System\Commands;

use Hyperdrive\Message;

/**
 * Record which commit is deployed and when it went live, for the /status page.
 *
 * @method static void call(string $commit)
 */
final class StampDeployment extends Message
{
    public const FILE = 'app/deploy.json';

    public function __construct(private readonly string $commit) {}

    public function handle(): void
    {
        file_put_contents(storage_path(self::FILE), json_encode([
            'commit' => $this->commit,
            'deployed_at' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
        ]).PHP_EOL);
    }
}
