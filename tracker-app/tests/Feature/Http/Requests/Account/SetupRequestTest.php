<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Enums\NotificationFrequency;
use App\Enums\TrooperTheme;
use App\Http\Requests\Account\SetupRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SetupRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    private string $base_organization_path = '001.';

    private string $other_organization_path = '999.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->trooper = Trooper::factory()->asMember()->create();
        $this->actingAs($this->trooper);
    }

    public function test_authorize_returns_true(): void
    {
        $subject = new SetupRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_legal_name(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::LEGAL_NAME]);
    }

    public function test_rules_requires_email(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::EMAIL, $rules);
        $this->assertContains('required', $rules[Trooper::EMAIL]);
    }

    public function test_rules_validates_email_format(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::EMAIL => 'not-an-email',
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_allows_same_email_for_current_user(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::EMAIL => $this->trooper->email,
        ]), $subject->rules());

        $this->assertFalse($validator->errors()->has(Trooper::EMAIL));
    }

    public function test_rules_rejects_email_used_by_another_trooper(): void
    {
        $other_trooper = Trooper::factory()->asMember()->create();
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::EMAIL => $other_trooper->email,
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_requires_notification_frequency(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::NOTIFICATION_FREQUENCY, $rules);
        $this->assertContains('required', $rules[Trooper::NOTIFICATION_FREQUENCY]);
    }

    public function test_rules_validates_notification_frequency_is_valid_enum(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::NOTIFICATION_FREQUENCY => 'invalid-frequency',
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::NOTIFICATION_FREQUENCY, $validator->errors()->toArray());
    }

    public function test_rules_accepts_instant_notification_frequency(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT->value,
        ]), $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_daily_notification_frequency(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::DAILY->value,
        ]), $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_never_notification_frequency(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::NEVER->value,
        ]), $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_requires_theme(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::THEME, $rules);
        $this->assertContains('required', $rules[Trooper::THEME]);
    }

    public function test_rules_validates_theme_is_valid_enum(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::THEME => 'invalid-theme',
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::THEME, $validator->errors()->toArray());
    }

    public function test_rules_accepts_sith_theme(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::THEME => TrooperTheme::SITH->value,
        ]), $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_theme_max_length(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::THEME => str_repeat('a', 17),
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::THEME, $validator->errors()->toArray());
    }

    public function test_rules_includes_organizations_array(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey('organizations', $rules);
    }

    public function test_rules_validates_valid_organization_assignment(): void
    {
        [$organization, , $leaf_unit] = $this->create_organization_tree($this->base_organization_path);

        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            'organizations' => [
                $organization->id => [
                    'assignment' => $leaf_unit->id,
                ],
            ],
        ]), $subject->rules());

        $this->assertFalse($validator->errors()->has("organizations.{$organization->id}.assignment"));
    }

    public function test_rules_rejects_assignment_that_is_not_descendant_of_organization(): void
    {
        [$organization] = $this->create_organization_tree($this->base_organization_path);
        [, , $other_leaf_unit] = $this->create_organization_tree($this->other_organization_path);

        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            'organizations' => [
                $organization->id => [
                    'assignment' => $other_leaf_unit->id,
                ],
            ],
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey("organizations.{$organization->id}.assignment", $validator->errors()->toArray());
    }

    public function test_rules_rejects_assignment_when_picked_organization_is_not_leaf_node(): void
    {
        [$organization, $region] = $this->create_organization_tree($this->base_organization_path);

        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            'organizations' => [
                $organization->id => [
                    'assignment' => $region->id,
                ],
            ],
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey("organizations.{$organization->id}.assignment", $validator->errors()->toArray());
    }

    public function test_rules_accepts_null_assignment_for_organization(): void
    {
        [$organization] = $this->create_organization_tree($this->base_organization_path);

        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            'organizations' => [
                $organization->id => [
                    'assignment' => null,
                ],
            ],
        ]), $subject->rules());

        $this->assertFalse($validator->errors()->has("organizations.{$organization->id}.assignment"));
    }

    public function test_rules_validates_email_max_length(): void
    {
        $long_email = str_repeat('a', 250) . '@example.com';
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::EMAIL => $long_email,
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_validates_legal_name_max_length(): void
    {
        $subject = new SetupRequest;
        $subject->setUserResolver(fn() => $this->trooper);

        $validator = Validator::make($this->valid_payload([
            Trooper::LEGAL_NAME => str_repeat('a', 257),
        ]), $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $validator->errors()->toArray());
    }

    /**
     * @return array<int, Organization>
     */
    private function create_organization_tree(string $path_prefix): array
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withName($path_prefix . '-organization')
            ->withNodePath($path_prefix)
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withName($path_prefix . '-region')
            ->withNodePath($path_prefix . '001.')
            ->create();

        $leaf_unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withName($path_prefix . '-unit')
            ->withNodePath($path_prefix . '001.001.')
            ->create();

        return [$organization, $region, $leaf_unit];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function valid_payload(array $overrides = []): array
    {
        $payload = [
            Trooper::LEGAL_NAME => 'Test Trooper',
            Trooper::EMAIL => 'setup-request-validation@example.com',
            Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            Trooper::NOTIFICATION_FREQUENCY => NotificationFrequency::INSTANT->value,
            'organizations' => [],
        ];

        return array_merge($payload, $overrides);
    }
}
