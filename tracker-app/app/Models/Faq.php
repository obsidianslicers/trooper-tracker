<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\Faq as BaseFaq;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Faq extends BaseFaq
{
    use HasFactory;
    use HasTrooperStamps;

    public function embedUrl(): ?string
    {
        if (! $this->video_url)
        {
            return null;
        }

        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $this->video_url, $matches))
        {
            return 'https://www.youtube.com/embed/' . $matches[1];
        }

        return $this->video_url;
    }
}
