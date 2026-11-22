<?php

declare(strict_types=1);

namespace App\Messages\Notices\PageData;

use App\Messages\Notices\Resources\NoticeFormResource;
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
            'type_options' => $this->getTypeOptions(),
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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getTypeOptions(): array
    {
        return [
            ['value' => 'info', 'label' => 'ℹ️ '.__('admin.NOW HEAR THIS!')],
            ['value' => 'success', 'label' => '✔️ '.__('admin.MISSION ACCOMPLISHED!')],
            ['value' => 'warning', 'label' => '⚠️ '.__('admin.ATTENTION TROOPERS!')],
            ['value' => 'danger', 'label' => '❌ '.__('admin.BATTLE STATIONS!')],
        ];
    }
}
