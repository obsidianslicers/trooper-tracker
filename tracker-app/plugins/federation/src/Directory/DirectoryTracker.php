<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Directory;

/**
 * One tracker on the supported list.
 */
final readonly class DirectoryTracker
{
    public function __construct(
        public string $name,
        public string $url,
    ) {}
}
