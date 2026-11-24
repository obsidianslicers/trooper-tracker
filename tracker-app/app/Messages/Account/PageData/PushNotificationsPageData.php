<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Messages\Account\Queries\GetTrooperNotifications;
use App\Messages\Account\Resources\PushNotificationResource;
use App\Messages\Account\Resources\TroopToConfirmResource;
use App\Messages\Events\Queries\GetTroopsToConfirm;
use App\Models\Trooper;
use App\Notifications\Events\EventShiftCompletedNotification;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The signed-in trooper's notifications, with the troops they still need to confirm.
 *
 * @method static array call(Actor $actor)
 */
final class PushNotificationsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    public function handle(): array
    {
        return [
            //  the reminders' troops are listed above, with their answers, as in the bell
            'notifications' => PushNotificationResource::collection(GetTrooperNotifications::call(
                trooper: $this->actor,
                except_type: EventShiftCompletedNotification::class,
            )),
            'troops_to_confirm' => TroopToConfirmResource::collection(
                GetTroopsToConfirm::call(trooper: $this->actor)
            ),
        ];
    }
}
