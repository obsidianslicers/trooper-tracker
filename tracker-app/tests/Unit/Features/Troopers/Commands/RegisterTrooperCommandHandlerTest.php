<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Features\Troopers\Commands\RegisterTrooperCommand;
use App\Features\Troopers\Commands\RegisterTrooperCommandHandler;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTrooperCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_trooper_with_member_role(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'phone' => '555-1234',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertInstanceOf(Trooper::class, $result);
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::EMAIL => 'member@example.com',
            Trooper::NAME => 'Test Member',
            Trooper::PHONE => '555-1234',
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);
    }

    public function test_invoke_creates_trooper_with_handler_role(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Handler',
            'email' => 'handler@example.com',
            'password' => 'password123',
            'account_type' => 'handler',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertDatabaseHas(Trooper::class, [
            Trooper::EMAIL => 'handler@example.com',
            Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER,
        ]);
    }

    public function test_invoke_hashes_password(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'password' => 'plain-password',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNotEquals('plain-password', $result->password);
        $this->assertTrue(Hash::check('plain-password', $result->password));
    }

    public function test_invoke_generates_password_when_not_provided(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNotNull($result->password);
        $this->assertNotEmpty($result->password);
    }

    public function test_invoke_sets_phone_to_null_when_not_provided(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNull($result->phone);
    }

    public function test_invoke_sets_setup_completed_at(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertNotNull($result->setup_completed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $result->setup_completed_at);
    }

    public function test_invoke_returns_created_trooper(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        $command = new RegisterTrooperCommand($valid_data);
        $subject = new RegisterTrooperCommandHandler();

        // Act
        $result = $subject($command);

        // Assert
        $this->assertInstanceOf(Trooper::class, $result);
        $this->assertTrue($result->exists);
        $this->assertEquals('Test Trooper', $result->name);
        $this->assertEquals('test@example.com', $result->email);
    }
}
