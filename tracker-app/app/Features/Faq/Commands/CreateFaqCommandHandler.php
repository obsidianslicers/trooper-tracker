<?php

declare(strict_types=1);

namespace App\Features\Faq\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Models\Faq;

/**
 * @implements CommandHandlerInterface<CreateFaqCommand>
 */
readonly class CreateFaqCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __invoke(object $message): Faq
    {
        $max_order = Faq::where(Faq::SECTION_ID, $message->section_id)->max(Faq::SORT_ORDER) ?? 0;

        $faq = new Faq;
        $faq->section_id = $message->section_id;
        $faq->title = $message->title;
        $faq->description = $message->description;
        $faq->video_url = $message->video_url;
        $faq->sort_order = $max_order + 1;

        $faq->save();

        return $faq;
    }
}
