<?php

namespace Tests\Unit\Rules\Auth;

use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UniqueOrganizationIdentifierRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_when_identifier_is_unique(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $subject = new UniqueOrganizationIdentifierRule($organization, null);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };

        // Act
        $subject->validate('identifier', 'TK-12345', $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed but it failed.');
    }

    public function test_validation_fails_when_identifier_is_not_unique(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();
        $organization->troopers()->attach($trooper, ['identifier' => 'TK-12345']);

        $subject = new UniqueOrganizationIdentifierRule($organization, null);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called, $organization): void
        {
            $fail_was_called = true;
            $this->assertEquals("{$organization->name} {$organization->identifier_display} already exists.", $message);
        };

        // Act
        $subject->validate('identifier', 'TK-12345', $fail);
    }

    public function test_validation_passes_when_identifier_is_empty(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $subject = new UniqueOrganizationIdentifierRule($organization, null);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };

        // Act
        $subject->validate('identifier', '', $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed but it failed.');
    }

    public function test_validation_does_not_fail_for_other_organizations(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $organization2 = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        // Attach the identifier to a different organization
        $organization2->troopers()->attach($trooper, ['identifier' => 'TK-12345']);

        $subject = new UniqueOrganizationIdentifierRule($organization1, null);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };

        // Act
        $subject->validate('identifier', 'TK-12345', $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed but it failed.');
    }

    public function test_validation_passes_when_trooper_updates_own_identifier(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();
        $organization->troopers()->attach($trooper, ['identifier' => 'TK-12345']);

        $subject = new UniqueOrganizationIdentifierRule($organization, $trooper);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };

        // Act - Trooper validates their own existing identifier
        $subject->validate('identifier', 'TK-12345', $fail);

        // Assert
        $this->assertFalse($fail_was_called, 'The validation rule should have passed when trooper updates own identifier.');
    }

    public function test_validation_fails_when_trooper_updates_to_another_troopers_identifier(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $organization->troopers()->attach($trooper1, ['identifier' => 'TK-12345']);
        $organization->troopers()->attach($trooper2, ['identifier' => 'TK-99999']);

        $subject = new UniqueOrganizationIdentifierRule($organization, $trooper2);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
        };

        // Act - Trooper2 tries to update their identifier to Trooper1's identifier
        $subject->validate('identifier', 'TK-12345', $fail);

        // Assert
        $this->assertTrue($fail_was_called, 'The validation rule should have failed when using another trooper\'s identifier.');
    }
}
