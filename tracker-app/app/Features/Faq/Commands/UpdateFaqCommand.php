<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Models\Faq;

readonly class UpdateFaqCommand
{
    public function __construct(
        public Faq $faq,
        public int $section_id,
        public string $title,
        public ?string $description,
        public ?string $video_url,
    ) {}
}
