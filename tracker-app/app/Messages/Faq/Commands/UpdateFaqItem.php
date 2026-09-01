<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static Faq call(Faq $faq, int $section_id, string $title, string|null $description, string|null $video_url)
 */
final class UpdateFaqItem extends Message
{
    public function __construct(
        private readonly Faq $faq,
        private readonly int $section_id,
        private readonly string $title,
        private readonly string|null $description,
        private readonly string|null $video_url,
    ) {
    }

    public function handle(): Faq
    {
        $this->faq->section_id = $this->section_id;
        $this->faq->title = $this->title;
        $this->faq->description = $this->description;
        $this->faq->video_url = $this->video_url;

        $this->faq->save();

        return $this->faq;
    }
}
