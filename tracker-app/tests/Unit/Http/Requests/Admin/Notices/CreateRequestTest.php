<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Notices;

use App\Enums\NoticeType;
use App\Http\Requests\Admin\Notices\CreateRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private CreateRequest $subject;
    private Trooper $user;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->subject->setUserResolver(fn() => $this->user);
    }

    public function test_authorize_returns_true_for_moderator(): void
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

    public function test_validation_passes_with_valid_data_for_moderator(): void
    {
        // Arrange
        $good_data = [
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_null_organization_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        $good_data = [
            'title' => 'Global Notice',
            'message' => 'This is a global notice.',
            'type' => NoticeType::WARNING->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => null,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_organization_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        $organization = Organization::factory()->create();

        $good_data = [
            'title' => 'Org-Specific Notice',
            'message' => 'This is an organization-specific notice.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $organization->id,
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
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
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
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_validation_fails_with_missing_message(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Test Notice',
            'message' => '',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
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
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => '',
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
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
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => 'invalid-type',
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
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
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => '',
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $this->organization->id,
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
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => '',
            'organization_id' => $this->organization->id,
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
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'ends_at' => Carbon::now()->format('Y-m-d'),
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('ends_at'));
    }

    public function test_validation_fails_with_missing_organization_id_for_moderator(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_fails_with_nonexistent_organization_id(): void
    {
        // Arrange
        $bad_data = [
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => 999999,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_fails_with_organization_not_moderated_by_user(): void
    {
        // Arrange
        $unmoderated_org = Organization::factory()->create();

        $bad_data = [
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $unmoderated_org->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_passes_with_child_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $moderator);

        $good_data = [
            'title' => 'Test Notice',
            'message' => 'This is a test notice message.',
            'type' => NoticeType::INFO->value,
            'starts_at' => Carbon::now()->format('Y-m-d'),
            'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
            'organization_id' => $unit->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
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
                'title' => 'Test Notice - ' . $type,
                'message' => 'This is a test notice message.',
                'type' => $type,
                'starts_at' => Carbon::now()->format('Y-m-d'),
                'ends_at' => Carbon::now()->addDays(7)->format('Y-m-d'),
                'organization_id' => $this->organization->id,
            ];

            // Act
            $this->subject->merge($good_data);
            $validator = Validator::make($good_data, $this->subject->rules());

            // Assert
            $this->assertTrue($validator->passes(), "Failed for type: {$type}");
        }
    }
}
