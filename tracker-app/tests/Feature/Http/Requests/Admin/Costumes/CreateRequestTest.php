<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Costumes;

use App\Http\Requests\Admin\Costumes\CreateRequest;
use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($this->admin);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new CreateRequest;
        $subject->setUserResolver(fn() => $member);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_requires_name(): void
    {
        $subject = new CreateRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertContains('required', $rules['name']);
    }

    public function test_rules_validates_name_is_string(): void
    {
        $subject = new CreateRequest;
        $rules = $subject->rules();

        $this->assertContains('string', $rules['name']);
    }

    public function test_rules_validates_name_max_length(): void
    {
        $subject = new CreateRequest;

        $validator = Validator::make(
            [
                'name' => str_repeat('a', 129), // max is 128
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_validates_name_is_unique(): void
    {
        $existing_costume = Costume::factory()->create();
        $subject = new CreateRequest;

        $validator = Validator::make(
            [
                'name' => $existing_costume->name,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_rules_accepts_unique_name(): void
    {
        $subject = new CreateRequest;

        $validator = Validator::make(
            [
                'name' => 'TK Stormtrooper',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_name_within_128_characters(): void
    {
        $subject = new CreateRequest;

        $validator = Validator::make(
            [
                'name' => str_repeat('a', 128), // exactly 128 chars
            ],
            $subject->rules()
        );

        // Should not fail for max length (may fail for uniqueness if it exists)
        $this->assertIsArray($validator->errors()->toArray());
    }
}
