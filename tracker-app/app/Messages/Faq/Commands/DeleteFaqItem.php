<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static void call(...$args)
 */
final class DeleteFaqItem extends Message
{
    public function __construct(
        public readonly Faq $faq,
    ) {}

    public function handle(): void
    {
        $this->faq->delete();
    }
}
