<?php

declare(strict_types=1);

namespace App\Messages\Faq\Commands;

use App\Models\Faq;
use Hyperdrive\Message;

/**
 * @method static Faq call(...$args)
 */
final class UpdateFaqItem extends Message
{
    public function __construct(
        public readonly Faq $faq,
        public readonly int $section_id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?string $video_url,
    ) {}

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
