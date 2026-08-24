<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\Faq;

readonly class UpdateFaqCommandHandler implements CommandHandlerInterface
{
    public function __invoke(object $message): Faq
    {
        $message->faq->section_id = $message->section_id;
        $message->faq->title = $message->title;
        $message->faq->description = $message->description;
        $message->faq->video_url = $message->video_url;

        $message->faq->save();

        return $message->faq;
    }
}
