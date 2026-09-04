<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static void call(FaqSection $section)
 */
final class DeleteFaqSection extends Message
{
    public function __construct(
        private readonly FaqSection $section,
    ) {
    }

    public function handle(): void
    {
        $this->section->delete();
    }
}
