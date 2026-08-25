<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static Faq call(...$args)
 */
final class CreateFaqItem extends Message
{
    public function __construct(
        public readonly int $section_id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $video_url,
    ) {}

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
