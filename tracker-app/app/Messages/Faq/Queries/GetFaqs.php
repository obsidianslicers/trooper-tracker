<?php

declare(strict_types=1);

namespace App\Messages\Faq\Queries;

use App\Models\FaqSection;
use Hyperdrive\Message;
use Illuminate\Support\Collection;
use App\Models\Faq;

/**
 * Retrieves all FAQs.

 * This query message responds with the FAQ sections data, which can be used by frontend clients
 * to display the available FAQ sections and manage FAQ assignments.
 *
 * @method static Collection call()
 */
final class GetFaqs extends Message
{
    public function __construct()
    {
    }

    /**
     * Retrieves all FAQ sections.

     * @return Collection A collection representing the FAQ sections, including section IDs and names
     */
    public function handle(): Collection
    {
        $relations = ['faqs' => function ($query)
        {
            $query->orderBy(Faq::SORT_ORDER)->orderBy(Faq::ID);
        }];

        return FaqSection::query()
            ->with($relations)
            ->orderBy(FaqSection::SORT_ORDER)
            ->orderBy(FaqSection::ID)
            ->get();
    }
}
