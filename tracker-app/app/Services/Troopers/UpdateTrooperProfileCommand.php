<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Models\Trooper;

/**
 * Command to update a trooper's profile information.
 *
 * This command updates any combination of the following trooper fields,
 * depending on which keys are present in the $data array:
 *
 * - name (string|null)
 * - email (string)
 * - phone (string|null)
 * - notification_frequency (\App\Enums\NotificationFrequency|string|int)
 * - theme (string|null)
 *
 * If $complete_setup is true, the trooper's setup_completed_at timestamp
 * will be set to the current time.
 *
 * @package App\Services\Troopers
 */
class UpdateTrooperProfileCommand
{
    /**
     * Apply profile updates to the given trooper.
     *
     * Expected $data keys (all optional):
     *
     *  - name: string|null
     *      The trooper's display name.
     *
     *  - email: string
     *      The trooper's email address.
     *
     *  - phone: string|null
     *      The trooper's phone number. Null clears the value.
     *
     *  - notification_frequency: \App\Enums\NotificationFrequency|string|int
     *      How often the trooper receives notification emails.
     *
     *  - theme: string|null
     *      UI theme preference. Null clears the value.
     *
     * @param Trooper $trooper
     *      The trooper being updated.
     *
     * @param array $data
     *      Validated profile fields to update. Only keys present in this array
     *      will be modified; missing keys are ignored.
     *
     * @param bool $complete_setup
     *      Whether to mark the trooper's setup as completed by setting
     *      setup_completed_at to now().
     *
     * @return void
     */
    public function __invoke(Trooper $trooper, array $data, bool $complete_setup = false): void
    {
        $fields = ['name', 'email', 'phone', 'notification_frequency', 'theme'];

        foreach ($fields as $field)
        {
            if (array_key_exists($field, $data))
            {
                $trooper->{$field} = $data[$field];
            }
        }

        if ($complete_setup)
        {
            $trooper->setup_completed_at = now();
        }

        $trooper->save();
    }
}

