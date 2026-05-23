<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqSection;
use Database\Factories\Base\FaqFactory as BaseFaqFactory;

class FaqFactory extends BaseFaqFactory
{
    public function definition(): array
    {
        return parent::definition();
    }

    public function withSection(FaqSection $section): static
    {
        return $this->state(fn(array $attributes) => [
            Faq::SECTION_ID => $section->{FaqSection::ID},
        ]);
    }

    public function withVideo(string $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'): static
    {
        return $this->state(fn(array $attributes) => [
            Faq::VIDEO_URL => $url,
        ]);
    }
}
