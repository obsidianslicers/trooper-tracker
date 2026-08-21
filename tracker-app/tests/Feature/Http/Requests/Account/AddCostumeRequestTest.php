<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Http\Requests\Account\AddCostumeRequest;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AddCostumeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new AddCostumeRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_validate_costume_id_as_integer_and_existing_record(): void
    {
        $costume = Costume::factory()->create();
        $organization = Organization::factory()->create();
        $subject = new AddCostumeRequest;

        OrganizationCostume::factory()->create([
            OrganizationCostume::COSTUME_ID => $costume->{Costume::ID},
            OrganizationCostume::ORGANIZATION_ID => $organization->{Organization::ID},
        ]);

        $subject->merge([
            'costume_id' => $costume->{Costume::ID},
            'organization_ids' => [$organization->{Organization::ID}],
        ]);

        $rules = $subject->rules();

        $this->assertArrayHasKey('costume_id', $rules);
        $this->assertContains('integer', $rules['costume_id']);

        $valid = Validator::make($subject->all(), $rules);
        $invalid_type = Validator::make([
            'costume_id' => 'not-an-int',
            'organization_ids' => [$organization->{Organization::ID}],
        ], $rules);
        $invalid_missing = Validator::make([
            'costume_id' => 999999,
            'organization_ids' => [999999],
        ], $rules);

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid_type->fails());
        $this->assertTrue($invalid_missing->fails());
        $this->assertArrayHasKey('costume_id', $invalid_type->errors()->toArray());
        $this->assertArrayHasKey('costume_id', $invalid_missing->errors()->toArray());
    }

    public function test_rules_require_organization_ids_to_exist_for_selected_costume(): void
    {
        $costume = Costume::factory()->create();
        $valid_organization = Organization::factory()->create();
        $invalid_organization = Organization::factory()->create();

        OrganizationCostume::factory()->create([
            OrganizationCostume::COSTUME_ID => $costume->{Costume::ID},
            OrganizationCostume::ORGANIZATION_ID => $valid_organization->{Organization::ID},
        ]);

        $subject = new AddCostumeRequest;
        $subject->merge([
            'costume_id' => $costume->{Costume::ID},
            'organization_ids' => [$valid_organization->{Organization::ID}],
        ]);

        $rules = $subject->rules();

        $valid = Validator::make($subject->all(), $rules);

        $invalid = Validator::make([
            'costume_id' => $costume->{Costume::ID},
            'organization_ids' => [$invalid_organization->{Organization::ID}],
        ], $rules);

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('organization_ids.0', $invalid->errors()->toArray());
    }
}
