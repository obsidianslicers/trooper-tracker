<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData\Sections;

use App\Messages\Faq\Queries\GetFaqSections;
use Hyperdrive\Message;

/**
 * @method static array call()
 */
final class ListFaqSectionsPageData extends Message
{
    public function handle(): array
    {
        return [
            'sections' => GetFaqSections::call()
        ];
    }
}
