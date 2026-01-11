<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Troopers;

use App\Enums\MembershipRole;
use App\Models\Trooper;
use App\Services\Troopers\RegisterTrooperCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tests for RegisterTrooperCommand.
 *
 * Verifies:
 * - Creates trooper with correct attributes from registration data.
 * - Hashes password before saving.
 * - Sets membership role based on account type.
 * - Marks setup as completed.
 * - Handles optional phone number.
 * - Generates password if not provided.
 */
class RegisterTrooperCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_trooper_with_member_role(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'John Doe',
            'email' => 'john.doe@gmail.com',
            'phone' => '555-1234',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertInstanceOf(Trooper::class, $trooper);
        $this->assertEquals('John Doe', $trooper->name);
        $this->assertEquals('john.doe@gmail.com', $trooper->email);
        $this->assertEquals('555-1234', $trooper->phone);
        $this->assertEquals(MembershipRole::MEMBER, $trooper->membership_role);
        $this->assertNotNull($trooper->setup_completed_at);
        $this->assertTrue(Hash::check('password123', $trooper->password));
    }

    public function test_invoke_creates_trooper_with_handler_role(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'Jane Smith',
            'email' => 'jane.smith@gmail.com',
            'phone' => '555-5678',
            'password' => 'secure456',
            'account_type' => 'handler',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertEquals('Jane Smith', $trooper->name);
        $this->assertEquals('jane.smith@gmail.com', $trooper->email);
        $this->assertEquals(MembershipRole::HANDLER, $trooper->membership_role);
        $this->assertTrue(Hash::check('secure456', $trooper->password));
    }

    public function test_invoke_handles_optional_phone_number(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'Bob Johnson',
            'email' => 'bob.johnson@gmail.com',
            'password' => 'password789',
            'account_type' => 'member',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertNull($trooper->phone);
        $this->assertEquals('Bob Johnson', $trooper->name);
        $this->assertEquals('bob.johnson@gmail.com', $trooper->email);
    }

    public function test_invoke_persists_trooper_to_database(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'Alice Williams',
            'email' => 'alice.williams@gmail.com',
            'password' => 'testpass',
            'account_type' => 'member',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertTrue($trooper->exists);
        $this->assertNotNull($trooper->id);
        $this->assertEquals('Alice Williams', $trooper->name);
        $this->assertEquals('alice.williams@gmail.com', $trooper->email);
        $this->assertEquals(MembershipRole::MEMBER, $trooper->membership_role);
    }

    public function test_invoke_generates_password_when_not_provided(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'Charlie Brown',
            'email' => 'charlie.brown@gmail.com',
            'account_type' => 'member',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertNotNull($trooper->password);
        $this->assertNotEmpty($trooper->password);
        $this->assertEquals('Charlie Brown', $trooper->name);
    }

    public function test_invoke_marks_setup_as_completed(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'Dave Miller',
            'email' => 'dave.miller@gmail.com',
            'password' => 'password',
            'account_type' => 'handler',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertNotNull($trooper->setup_completed_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $trooper->setup_completed_at);
    }

    public function test_invoke_hashes_password_before_saving(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $plain_password = 'my-secure-password';

        $registration_data = [
            'name' => 'Eve Taylor',
            'email' => 'eve.taylor@gmail.com',
            'password' => $plain_password,
            'account_type' => 'member',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertNotEquals($plain_password, $trooper->password);
        $this->assertTrue(Hash::check($plain_password, $trooper->password));
        $this->assertTrue($trooper->exists);
    }

    public function test_invoke_returns_saved_trooper_instance(): void
    {
        // Arrange
        $subject = new RegisterTrooperCommand();

        $registration_data = [
            'name' => 'Frank Davis',
            'email' => 'frank.davis@gmail.com',
            'password' => 'testpass123',
            'account_type' => 'member',
        ];

        // Act
        $trooper = $subject($registration_data);

        // Assert
        $this->assertInstanceOf(Trooper::class, $trooper);
        $this->assertTrue($trooper->exists);
        $this->assertNotNull($trooper->id);
        $this->assertGreaterThan(0, $trooper->id);
    }
}
