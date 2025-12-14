<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Awards;

use App\Http\Requests\Admin\Awards\UpdateRequest;
use App\Models\Award;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateRequest $subject;
    private Trooper $user;
    private Organization $organization;
    private Award $award;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->award = Award::factory()->for($this->organization)->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['award' => $this->award]);
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

    public function test_authorize_throws_exception_when_award_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Award not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_name(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Updated Award Name',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_name(): void
    {
        // Arrange
        $bad_data = [
            'name' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => str_repeat('a', 129),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_passes_with_name_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'name' => str_repeat('a', 128),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_authorize_returns_true_for_moderator_of_parent_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $unit_award = Award::factory()->for($unit)->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $moderator);
        $this->subject->setRouteResolver(function () use ($unit_award)
        {
            return $this->getMockRoute(['award' => $unit_award]);
        });

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }
}
