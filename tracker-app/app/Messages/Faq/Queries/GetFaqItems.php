<?php

declare(strict_types=1);

namespace App\Messages\Faq\Queries;

use App\Models\Faq;
use App\Models\FaqSection;
use Hyperdrive\Message;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Retrieves all FAQ items.
 *
 * This query message responds with the FAQ items data, which can be used by frontend clients
 * to display the available FAQ items and manage FAQ viewing.
 *
 * @method static Collection|LengthAwarePaginator call(int|null $section_id = null, int $page_size = 20)
 */
final class GetFaqItems extends Message
{
    public function __construct(
        private readonly int|null $section_id = null,
        private readonly int $page_size = 20
    ) {
    }

    /**
     * Retrieves all FAQ items.
     *
     * @return Collection|LengthAwarePaginator A collection representing the FAQ items, or a paginated result
     */
    public function handle(): Collection|LengthAwarePaginator
    {
        $query = Faq::query()->with('faq_section')->orderBy(Faq::SORT_ORDER)->orderBy(Faq::ID);

        if ($this->section_id)
        {
            $query->where(Faq::SECTION_ID, $this->section_id);
        }

        if ($this->section_id !== null)
        {
            return $query->get();
        }

        return $query->paginate($this->page_size)->withQueryString();
    }
}
