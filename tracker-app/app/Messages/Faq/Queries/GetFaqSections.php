<?php

declare(strict_types=1);

namespace App\Messages\Faq\Queries;

use App\Models\FaqSection;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Retrieves all FAQ sections.

 * This query message responds with the FAQ sections data, which can be used by frontend clients
 * to display the available FAQ sections and manage FAQ assignments.
 *
 * @method static Collection call()
 */
final class GetFaqSections extends Message
{
    /**
     * Retrieves all FAQ sections.

     * @return Collection A collection representing the FAQ sections, including section IDs and names
     */
    public function handle(): Collection
    {
        return FaqSection::withCount('faqs')
            ->orderBy(FaqSection::SORT_ORDER)
            ->orderBy(FaqSection::ID)
            ->get();
    }
}
