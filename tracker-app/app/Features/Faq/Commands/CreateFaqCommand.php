<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

readonly class CreateFaqCommand
{
    public function __construct(
        public int $section_id,
        public string $title,
        public ?string $description,
        public ?string $video_url,
    ) {}
}
