<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static Faq call(Faq $item, int $section_id, string $title, string|null $description, string|null $video_url)
 */
final class UpdateFaqItem extends Message
{
    public function __construct(
        private readonly Faq $item,
        private readonly int $section_id,
        private readonly string $title,
        private readonly string|null $description,
        private readonly string|null $video_url,
    ) {
    }

    public function handle(): Faq
    {
        $this->item->section_id = $this->section_id;
        $this->item->title = $this->title;
        $this->item->description = $this->description;
        $this->item->video_url = $this->video_url;

        $this->item->save();

        return $this->item;
    }
}
