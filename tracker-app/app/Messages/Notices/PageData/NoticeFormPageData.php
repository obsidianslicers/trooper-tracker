<?php

declare(strict_types=1);

namespace App\Messages\Notices\PageData;

use App\Messages\Notices\Resources\NoticeFormResource;
use App\Messages\Notices\Resources\NoticeTypeOptions;
use App\Messages\Organizations\Queries\GetModeratedOrganizationOptions;
use App\Models\Notice;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The notice create and update forms: the notice, its type choices and, when creating,
 * the organizations the actor may post to.
 *
 * @method static array call(Actor $actor, Notice $notice)
 */
final class NoticeFormPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly Notice $notice,
    ) {}

    public function handle(): array
    {
        $data = [
            'notice' => new NoticeFormResource($this->notice),
            'type_options' => new NoticeTypeOptions,
        ];

        if (!$this->notice->exists)
        {
            $data['organization_options'] = GetModeratedOrganizationOptions::call(
                trooper: $this->actor,
                with_everyone: true,
            );
        }

        return $data;
    }
}
