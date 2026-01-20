<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Commands;

use App\Features\Troopers\Commands\RegisterTrooperCommand;
use Tests\TestCase;

class RegisterTrooperCommandTest extends TestCase
{
    public function test_construct_with_valid_data(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'password' => 'password123',
            'account_type' => 'member',
        ];

        // Act
        $subject = new RegisterTrooperCommand($valid_data);

        // Assert
        $this->assertEquals($valid_data, $subject->valid_data);
    }

    public function test_construct_with_minimal_data(): void
    {
        // Arrange
        $valid_data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'handler',
        ];

        // Act
        $subject = new RegisterTrooperCommand($valid_data);

        // Assert
        $this->assertEquals($valid_data, $subject->valid_data);
    }
}
