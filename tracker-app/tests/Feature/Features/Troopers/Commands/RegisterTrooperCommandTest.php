<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Features\Troopers\Commands\RegisterTrooperCommand;
use Tests\TestCase;

/**
 * @see RegisterTrooperCommand
 */
class RegisterTrooperCommandTest extends TestCase
{
    public function test_constructor_stores_valid_data(): void
    {
        $valid_data = [
            'legal_name' => 'John Doe',
            'display_name' => 'JD',
            'email' => 'jd@example.com',
            'phone' => '555-1234',
            'password' => 'secret123',
            'account_type' => 'member',
        ];

        $subject = new RegisterTrooperCommand(valid_data: $valid_data);

        $this->assertSame($valid_data, $subject->valid_data);
    }
}
