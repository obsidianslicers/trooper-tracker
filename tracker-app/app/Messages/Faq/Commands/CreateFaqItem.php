<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static Faq call(int $section_id, string $title, string|null $description, string|null $video_url)
 */
final class CreateFaqItem extends Message
{
    public function __construct(
        private readonly int $section_id,
        private readonly string $title,
        private readonly string|null $description,
        private readonly string|null $video_url,
    ) {
    }

    public function handle(): Faq
    {
        $max_order = Faq::where(Faq::SECTION_ID, $this->section_id)->max(Faq::SORT_ORDER) ?? 0;

        $faq = new Faq;
        $faq->section_id = $this->section_id;
        $faq->title = $this->title;
        $faq->description = $this->description;
        $faq->video_url = $this->video_url;
        $faq->sort_order = $max_order + 1;

        $faq->save();

        return $faq;
    }
}
