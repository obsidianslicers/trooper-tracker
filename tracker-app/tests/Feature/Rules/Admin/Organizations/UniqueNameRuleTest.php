<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Organizations;

use App\Models\Organization;
use App\Rules\Admin\Organizations\UniqueNameRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueNameRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creation_fails_when_name_exists_among_parent_children(): void
    {
        $parent = Organization::factory()->withName('Florida Garrison')->create();

        Organization::factory()
            ->withParent($parent)
            ->withName('Orlando Squad')
            ->create();

        $validator = Validator::make([
            'name' => 'Orlando Squad',
        ], [
            'name' => [new UniqueNameRule(false, $parent)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Florida Garrison Name already exists.',
            $validator->errors()->first('name')
        );
    }

    public function test_creation_passes_when_name_is_unique_among_parent_children(): void
    {
        $parent = Organization::factory()->withName('Florida Garrison')->create();

        Organization::factory()
            ->withParent($parent)
            ->withName('Orlando Squad')
            ->create();

        $validator = Validator::make([
            'name' => 'Tampa Squad',
        ], [
            'name' => [new UniqueNameRule(false, $parent)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_root_update_fails_when_duplicate_root_name_exists(): void
    {
        $subject = Organization::factory()->withName('Florida Garrison')->create();

        Organization::factory()
            ->withName('Makaze Squad')
            ->create();

        $validator = Validator::make([
            'name' => 'Makaze Squad',
        ], [
            'name' => [new UniqueNameRule(true, $subject)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Florida Garrison Name already exists.',
            $validator->errors()->first('name')
        );
    }

    public function test_child_update_fails_when_sibling_has_same_name(): void
    {
        $parent = Organization::factory()->withName('Florida Garrison')->create();

        $subject = Organization::factory()
            ->withParent($parent)
            ->withName('Orlando Squad')
            ->create();

        Organization::factory()
            ->withParent($parent)
            ->withName('Tampa Squad')
            ->create();

        $validator = Validator::make([
            'name' => 'Tampa Squad',
        ], [
            'name' => [new UniqueNameRule(true, $subject)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Orlando Squad Name already exists.',
            $validator->errors()->first('name')
        );
    }

    public function test_child_update_passes_when_name_remains_unchanged_for_same_record(): void
    {
        $parent = Organization::factory()->withName('Florida Garrison')->create();

        $subject = Organization::factory()
            ->withParent($parent)
            ->withName('Orlando Squad')
            ->create();

        $validator = Validator::make([
            'name' => 'Orlando Squad',
        ], [
            'name' => [new UniqueNameRule(true, $subject)],
        ]);

        $this->assertTrue($validator->passes());
    }
}
