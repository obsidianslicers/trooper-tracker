<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\SynchronizerInterface;
use App\Models\Event;
use App\Models\Organization;
use RuntimeException;

abstract class BaseOrganizationService implements SynchronizerInterface
{
    public function __construct(protected readonly Organization $organization)
    {
    }

    public abstract function syncCostumes(): void;

    public abstract function syncAllMembers(): void;

    public abstract function syncMember(string $identifier): void;

    protected function cleanInput($value): mixed
    {
        $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return $value;
    }

    public static function parseRequestAppearance(string $message): Event
    {
        throw new RuntimeException('Not implemented');
    }

    protected static function parseMessage(string $message): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $message);
        $parsed = [];
        $currentKey = null;

        foreach ($lines as $line)
        {
            $line = trim($line);
            if ($line === '')
            {
                continue; // skip empty lines
            }

            if (strpos($line, ':') !== false)
            {
                // New identifier line
                [$key, $value] = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);

                $currentKey = $key;
                $parsed[$currentKey] = $value;
            }
            else
            {
                // Continuation of previous value
                if ($currentKey !== null)
                {
                    $parsed[$currentKey] .= ' ' . $line;
                }
            }
        }

        return $parsed;
    }
}