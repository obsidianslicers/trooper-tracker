<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Notices;

use App\Enums\NoticeType;
use App\Http\Requests\Admin\Notices\CreateRequest;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Trooper $moderator;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->actingAs($this->admin);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_title(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::TITLE, $rules);
        $this->assertContains('required', $rules[Notice::TITLE]);
    }

    public function test_rules_validates_title_max_length(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $validator = Validator::make(
            [
                Notice::TITLE => str_repeat('a', 129), // max is 128
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::TITLE, $validator->errors()->toArray());
    }

    public function test_rules_requires_message(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::MESSAGE, $rules);
        $this->assertContains('required', $rules[Notice::MESSAGE]);
    }

    public function test_rules_requires_type(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::TYPE, $rules);
        $this->assertContains('required', $rules[Notice::TYPE]);
    }

    public function test_rules_validates_type_is_valid_enum(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => 'invalid-type',
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::TYPE, $validator->errors()->toArray());
    }

    public function test_rules_accepts_info_type(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_requires_starts_at(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::STARTS_AT, $rules);
        $this->assertContains('required', $rules[Notice::STARTS_AT]);
    }

    public function test_rules_requires_ends_at(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::ENDS_AT, $rules);
        $this->assertContains('required', $rules[Notice::ENDS_AT]);
    }

    public function test_rules_validates_ends_at_is_after_starts_at(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->addDays(7)->format('Y-m-d'),
                Notice::ENDS_AT => now()->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::ENDS_AT, $validator->errors()->toArray());
    }

    public function test_rules_organization_id_is_nullable_for_admin(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::ORGANIZATION_ID, $rules);
        $this->assertContains('nullable', $rules[Notice::ORGANIZATION_ID]);
    }

    public function test_rules_organization_id_is_required_for_moderator(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::ORGANIZATION_ID, $rules);
        $this->assertContains('required', $rules[Notice::ORGANIZATION_ID]);
    }

    public function test_rules_validates_organization_exists(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
                Notice::ORGANIZATION_ID => 999999,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::ORGANIZATION_ID, $validator->errors()->toArray());
    }

    public function test_rules_validates_moderator_can_only_select_moderated_organizations(): void
    {
        $other_organization = Organization::factory()->create();
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->moderator);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
                Notice::ORGANIZATION_ID => $other_organization->id,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
    }
}
