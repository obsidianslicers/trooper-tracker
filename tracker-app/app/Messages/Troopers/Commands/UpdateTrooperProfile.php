<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\TrooperTheme;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's profile information.
 * 
 * @method static void call()
 */
readonly class UpdateTrooperProfile extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly string $legal_name,
        private readonly string $display_name,
        private readonly TrooperTheme $theme,
        private readonly string|null $phone,
        private readonly int|null $display_costume_id,
    ) {
    }

    /**
     * Execute the command to update trooper profile.
     *
     * @return null
     */
    public function __invoke(): void
    {
        $this->trooper->legal_name = $this->legal_name;
        $this->trooper->display_name = $this->display_name;
        $this->trooper->theme = $this->theme;
        $this->trooper->phone = $this->phone;
        $this->trooper->display_costume_id = $this->display_costume_id;

        $this->trooper->save();
    }
}
