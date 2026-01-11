<?php

declare(strict_types=1);

namespace App\Services\Troopers;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a new trooper account during registration.
 *
 * This command handles the core trooper creation logic, setting the initial
 * profile data and membership role based on the registration form data.
 * It marks the setup as completed immediately, as the registration flow
 * includes all required initial information.
 */
class RegisterTrooperCommand
{
    /**
     * Create and save a new trooper from registration data.
     *
     * @param array $data The registration form data containing:
     *                    - name (string): The trooper's display name
     *                    - email (string): The trooper's email address
     *                    - phone (string|null): Optional phone number
     *                    - password (string|null): The trooper's password (will be hashed)
     *                    - account_type (string): Either 'member' or 'handler'
     * @return Trooper The newly created trooper instance
     */
    public function __invoke(array $data): Trooper
    {
        $trooper = new Trooper();

        $trooper->name = $data['name'];
        $trooper->email = $data['email'];
        $trooper->phone = $data['phone'] ?? null;
        $trooper->password = Hash::make($data['password'] ?? uniqid());
        $trooper->membership_role = $data['account_type'] === 'member'
            ? MembershipRole::MEMBER
            : MembershipRole::HANDLER;
        $trooper->setup_completed_at = now();

        $trooper->save();

        return $trooper;
    }
}
