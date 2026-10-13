<?php

declare(strict_types=1);

namespace Database\Seeders\Issues\Support;

/**
 * The outcome of checking stored credit against what was possible at the time of the shift.
 */
readonly class CreditCheck
{
    /**
     * @param  array<int, int>  $keep_ids  stored ids that are supported
     * @param  array<int, int>  $remove_ids  stored ids that are impossible for this shift
     * @param  array<int, int>  $unknown_root_ids  roots kept only because nothing proves them wrong
     */
    public function __construct(
        public array $keep_ids,
        public array $remove_ids,
        public array $unknown_root_ids,
        public ?string $note = null,
    ) {}
}
