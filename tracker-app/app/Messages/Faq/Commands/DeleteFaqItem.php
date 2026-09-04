<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static void call(Faq $faq)
 */
final class DeleteFaqItem extends Message
{
    public function __construct(
        private readonly Faq $faq,
    ) {
    }

    public function handle(): void
    {
        $this->faq->delete();
    }
}
