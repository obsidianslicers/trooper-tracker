<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\RegisterTrooperCommand;
use App\Features\Troopers\Commands\RegisterTrooperCommandHandler;
use App\Enums\MembershipRole;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @see RegisterTrooperCommandHandler
 */
class RegisterTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_trooper_with_provided_data(): void
    {
        $valid_data = [
            'legal_name' => 'Jane Smith',
            'display_name' => 'Jane S',
            'email' => 'jane@example.com',
            'phone' => '555-9876',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand(valid_data: $valid_data);
        $handler = app(RegisterTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertInstanceOf(Trooper::class, $result);
        $this->assertDatabaseHas('tt_troopers', [
            Trooper::LEGAL_NAME => 'Jane Smith',
            Trooper::DISPLAY_NAME => 'Jane S',
            Trooper::EMAIL => 'jane@example.com',
            Trooper::PHONE => '555-9876',
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER->value,
        ]);
        $this->assertNotNull($result->setup_completed_at);
    }

    public function test_invoke_hashes_password(): void
    {
        $valid_data = [
            'legal_name' => 'Bob Jones',
            'display_name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'plaintext-password',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand(valid_data: $valid_data);
        $handler = app(RegisterTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertNotEquals('plaintext-password', $result->password);
        $this->assertTrue(Hash::check('plaintext-password', $result->password));
    }

    public function test_invoke_handles_missing_phone(): void
    {
        $valid_data = [
            'legal_name' => 'Alice Brown',
            'display_name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand(valid_data: $valid_data);
        $handler = app(RegisterTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertNull($result->phone);
    }

    public function test_invoke_generates_password_when_missing(): void
    {
        $valid_data = [
            'legal_name' => 'Charlie Davis',
            'display_name' => 'Charlie',
            'email' => 'charlie@example.com',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand(valid_data: $valid_data);
        $handler = app(RegisterTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertNotNull($result->password);
        $this->assertNotEmpty($result->password);
    }

    public function test_invoke_sets_member_role(): void
    {
        $valid_data = [
            'legal_name' => 'Member User',
            'display_name' => 'Member',
            'email' => 'member@example.com',
            'password' => 'password',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand(valid_data: $valid_data);
        $handler = app(RegisterTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertEquals(MembershipRole::MEMBER, $result->membership_role);
    }

    public function test_invoke_sets_handler_role(): void
    {
        $valid_data = [
            'legal_name' => 'Handler User',
            'display_name' => 'Handler',
            'email' => 'handler@example.com',
            'password' => 'password',
            'account_type' => 'handler',
        ];

        $command = new RegisterTrooperCommand(valid_data: $valid_data);
        $handler = app(RegisterTrooperCommandHandler::class);

        $result = $handler($command);

        $this->assertEquals(MembershipRole::HANDLER, $result->membership_role);
    }
}
