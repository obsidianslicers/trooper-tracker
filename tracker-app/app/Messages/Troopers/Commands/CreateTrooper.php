<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the registration of a new trooper, including setting up their basic information,
 * membership role, and linking to a guardian if applicable.
 * This command is responsible for creating a new trooper record in the database.
 * It ensures that all required fields are set and handles the association with a guardian if provided.
 *
 * @method static Trooper call(string $legal_name, string $display_name, string $email, MembershipRole $membership_role, string|null $password = null, string|null $phone = null, string|null $date_of_birth = null, string|null $guardian_email = null)
 */
final class CreateTrooper extends Message
{
    public function __construct(
        public readonly string $legal_name,
        public readonly string $display_name,
        public readonly string $email,
        public readonly MembershipRole $membership_role,
        public readonly ?string $password = null,
        public readonly ?string $phone = null,
        public readonly ?string $date_of_birth = null,
        public readonly ?string $guardian_email = null,
    ) {}

    public function handle(): Trooper
    {
        $trooper = new Trooper;

        $trooper->legal_name = $this->legal_name;
        $trooper->display_name = $this->display_name;
        $trooper->email = $this->email;
        $trooper->phone = $this->phone ?? null;
        $trooper->password = Hash::make($this->password ?? uniqid());
        $trooper->membership_role = $this->membership_role;

        $trooper->setup_completed_at = now();

        if ($this->guardian_email)
        {
            $trooper->date_of_birth = $this->date_of_birth;

            $guardian = Trooper::where(Trooper::EMAIL, $this->guardian_email)->first();

            if ($guardian)
            {
                $trooper->guardian_id = $guardian->id;
            }
        }

        $trooper->save();

        return $trooper;
    }
}
