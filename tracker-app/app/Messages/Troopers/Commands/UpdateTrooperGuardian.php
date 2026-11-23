<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Saves a minor's guardian details. A guardian email links the trooper with that email as guardian;
 * an empty one clears the link.
 *
 * @method static void call(Trooper $trooper, array $valid_data)
 */
final class UpdateTrooperGuardian extends Message
{
    /**
     * @param  array<string, mixed>  $valid_data  The guardian form, which may carry guardian_email
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $valid_data,
    ) {}

    public function handle(): void
    {
        $data = $this->valid_data;

        if (array_key_exists('guardian_email', $data))
        {
            $guardian_email = $data['guardian_email'];
            $data[Trooper::GUARDIAN_ID] = $guardian_email
                ? Trooper::where(Trooper::EMAIL, $guardian_email)->value(Trooper::ID)
                : null;
            unset($data['guardian_email']);
        }

        UpdateTrooper::call(trooper: $this->trooper, valid_data: $data);
    }
}
