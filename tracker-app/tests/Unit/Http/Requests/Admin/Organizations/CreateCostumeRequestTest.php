<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Organizations;

use App\Http\Requests\Admin\Organizations\CreateCostumeRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Admin\Organizations\UniqueCostumeNameRule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the CreateCostumeRequest class.
 *
 * Validates authorization logic and rule generation for costume creation.
 */
class CreateCostumeRequestTest extends TestCase
{
    use RefreshDatabase;

    private CreateCostumeRequest $subject;
    private Trooper $user;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateCostumeRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['organization' => $this->organization]);
        });
    }

    private function getMockRoute(array $parameters = []): object
    {
        return new class ($parameters)
        {
            public function __construct(private array $parameters)
            {
            }

            public function parameter(string $key, $default = null)
            {
                return $this->parameters[$key] ?? $default;
            }
        };
    }

    public function test_authorize_returns_true_for_moderator_of_organization(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_moderator_of_different_organization(): void
    {
        // Arrange
        $different_org = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($different_org, moderator: true)
            ->create();
        $this->subject->setUserResolver(fn() => $moderator);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_throws_exception_when_organization_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Organization not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_rules_returns_validation_rules_for_costume_name(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertArrayHasKey('name', $rules);
        $this->assertIsArray($rules['name']);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:128', $rules['name']);

        // Check that UniqueCostumeNameRule is present
        $has_unique_rule = false;
        foreach ($rules['name'] as $rule)
        {
            if ($rule instanceof UniqueCostumeNameRule)
            {
                $has_unique_rule = true;
                break;
            }
        }
        $this->assertTrue($has_unique_rule, 'Expected UniqueCostumeNameRule to be present in validation rules');
    }
}
