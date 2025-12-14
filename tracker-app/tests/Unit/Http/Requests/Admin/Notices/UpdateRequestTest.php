<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Notices;

use App\Enums\NoticeType;
use App\Http\Requests\Admin\Notices\UpdateRequest;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
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
    private Notice $notice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->notice = Notice::factory()->withOrganization($this->organization)->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['notice' => $this->notice]);
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

    public function test_authorize_throws_exception_when_notice_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Notice not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $good_data = [
            'title' => 'Updated Notice Title',
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_title(): void
    {
        // Arrange
        $bad_data = [
            'title' => '',
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_validation_fails_with_title_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'title' => str_repeat('a', 129),
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_validation_passes_with_title_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'title' => str_repeat('a', 128),
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_message(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Updated Notice Title',
            'message' => '',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('message'));
    }

    public function test_validation_fails_with_missing_type(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Updated Notice Title',
            'message' => 'Updated notice message.',
            'type' => '',
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('type'));
    }

    public function test_validation_fails_with_invalid_type(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Updated Notice Title',
            'message' => 'Updated notice message.',
            'type' => 'invalid-type',
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('type'));
    }

    public function test_validation_fails_with_missing_starts_at(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Updated Notice Title',
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => '',
            'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('starts_at'));
    }

    public function test_validation_fails_with_missing_ends_at(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Updated Notice Title',
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('ends_at'));
    }

    public function test_validation_fails_when_ends_at_is_before_starts_at(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Updated Notice Title',
            'message' => 'Updated notice message.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
            'ends_at' => Carbon::now()->format('Y-m-d'),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('ends_at'));
    }

    public function test_validation_passes_with_all_notice_types(): void
    {
        // Arrange
        $types = [
            NoticeType::INFO->value,
            NoticeType::WARNING->value,
            NoticeType::SUCCESS->value,
        ];

        foreach ($types as $type)
        {
            $good_data = [
                'title' => 'Updated Notice - ' . $type,
                'message' => 'Updated notice message.',
                'type' => $type,
                'starts_at' => Carbon::now()->format('Y-m-d'),
                'ends_at' => Carbon::now()->addDays(14)->format('Y-m-d'),
            ];

            // Act
            $this->subject->merge($good_data);
            $validator = Validator::make($good_data, $this->subject->rules());

            // Assert
            $this->assertTrue($validator->passes(), "Failed for type: {$type}");
        }
    }

    public function test_authorize_returns_true_for_moderator_of_parent_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $unit_notice = Notice::factory()->withOrganization($unit)->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $moderator);
        $this->subject->setRouteResolver(function () use ($unit_notice)
        {
            return $this->getMockRoute(['notice' => $unit_notice]);
        });

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_administrator_on_global_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $global_notice = Notice::factory()->create(['organization_id' => null]);

        $this->subject->setUserResolver(fn() => $admin);
        $this->subject->setRouteResolver(function () use ($global_notice)
        {
            return $this->getMockRoute(['notice' => $global_notice]);
        });

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }
}
