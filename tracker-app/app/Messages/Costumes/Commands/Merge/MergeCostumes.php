<?php

declare(strict_types=1);

namespace App\Messages\Costumes\Commands\Merge;

use App\Models\Costume;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use InvalidArgumentException;

/**
 * Folds a duplicate (source) costume into the costume that should be kept (target).
 *
 * Every reference to the source moves to the target: club costume links, the troopers
 * approved for them, display costumes, and event sign-ups (primary and backup). The
 * source costume is then permanently deleted.
 *
 * @method static void call(Costume $target_costume, Costume $source_costume)
 */
final class MergeCostumes extends Message
{
    use ShouldBeTransactional;

    public function __construct(
        private readonly Costume $target_costume,
        private readonly Costume $source_costume,
    ) {}

    public function handle(): void
    {
        if ($this->target_costume->id === $this->source_costume->id)
        {
            throw new InvalidArgumentException('A costume cannot be merged into itself.');
        }

        MergeOrganizationCostumes::call(
            target_costume: $this->target_costume,
            source_costume: $this->source_costume,
        );
        MergeEventTrooperCostumes::call(
            target_costume: $this->target_costume,
            source_costume: $this->source_costume,
        );

        $this->source_costume->forceDelete();
    }
}
