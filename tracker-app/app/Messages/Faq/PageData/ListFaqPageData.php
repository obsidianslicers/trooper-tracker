<?php

declare(strict_types=1);

namespace App\Messages\Faq\PageData;

use App\Models\Faq;
use App\Models\FaqSection;
use Hyperdrive\Message;

/**
 * @method static array call(...$args)
 */
final class ListFaqPageData extends Message
{
    public function __construct(
        public readonly ?int $section_id,
    ) {}

    public function handle(): array
    {
        $query = Faq::query()->with('faq_section')->orderBy(Faq::SORT_ORDER)->orderBy(Faq::ID);

        if ($this->section_id)
        {
            $query->where(Faq::SECTION_ID, $this->section_id);
        }

        $sortable = $this->section_id !== null;
        $items = $sortable ? $query->get() : $query->paginate(20)->withQueryString();

        return [
            'items' => $items,
            'sections' => FaqSection::orderBy(FaqSection::SORT_ORDER)->get(),
            'section_id' => $this->section_id,
            'sortable' => $sortable,
        ];
    }
}
