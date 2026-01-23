<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Support\Facades\Hash;

/**
 * Handler for registering a new trooper.
 *
 * Fills the trooper model with validated data and saves.
 * If complete_setup flag is true, sets the setup_completed_at timestamp
 * to mark the trooper's initial profile setup as complete.
 *
 * @implements CommandHandlerInterface<RegisterTrooperCommand>
 */
readonly class RegisterTrooperCommandHandler implements CommandHandlerInterface
{
    /**
     * Execute the command to register a new trooper.
     *
     * Creates a new Trooper record with the provided data. Hashes the password
     * if provided, otherwise generates a unique password. Always sets
     * setup_completed_at to mark registration as complete.
     *
     * @param RegisterTrooperCommand $message The command with trooper registration data
     * @return Trooper
     */
    public function __invoke(object $message): mixed
    {
        $trooper = new Trooper();

        $trooper->name = $message->valid_data['name'];
        $trooper->email = $message->valid_data['email'];
        $trooper->phone = $message->valid_data['phone'] ?? null;
        $trooper->password = Hash::make($message->valid_data['password'] ?? uniqid());
        $trooper->membership_role = $message->valid_data['account_type'] === 'member'
            ? MembershipRole::MEMBER
            : MembershipRole::HANDLER;
        $trooper->setup_completed_at = now();

        $trooper->save();

        return $trooper;
    }
}

