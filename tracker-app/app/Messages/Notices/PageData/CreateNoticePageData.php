<?php

declare(strict_types=1);

namespace App\Messages\Notices\PageData;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The create notice form: blank, a copy of a notice the actor moderates, or set to one of
 * the actor's organizations.
 *
 * @method static array call(Actor $actor, int|null $copy_id = null, int|null $organization_id = null)
 */
final class CreateNoticePageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?int $copy_id = null,
        private readonly ?int $organization_id = null,
    ) {}

    public function handle(): array
    {
        $notice = $this->newNotice();

        if ($this->organization_id !== null)
        {
            $notice->organization_id = $this->organization_id;
        }

        if ($notice->organization_id !== null)
        {
            $organization = Organization::moderatedBy($this->actor)
                ->findOrFail($notice->organization_id);

            $notice->setRelation('organization', $organization);
        }

        return NoticeFormPageData::call(actor: $this->actor, notice: $notice);
    }

    private function newNotice(): Notice
    {
        if ($this->copy_id === null)
        {
            $notice = new Notice;
            $notice->type = NoticeType::INFO;

            return $notice;
        }

        $copy = Notice::moderatedBy($this->actor)->findOrFail($this->copy_id);

        $notice = $copy->replicate();
        $notice->title = __('admin.:title (Copy)', ['title' => $copy->title]);

        return $notice;
    }
}
