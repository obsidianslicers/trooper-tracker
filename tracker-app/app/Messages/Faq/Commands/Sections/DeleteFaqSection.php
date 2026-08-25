<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands\Sections;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static void call(...$args)
 */
final class DeleteFaqSection extends Message
{
    public function __construct(
        public readonly FaqSection $section,
    ) {}

    public function handle(): void
    {
        $this->section->delete();
    }
}
