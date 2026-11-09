<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Costumes;

use App\Http\Requests\Admin\Costumes\MergeCostumesRequest;
use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MergeCostumesRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new MergeCostumesRequest;
        $subject->setUserResolver(fn () => Trooper::factory()->asSystemOwner()->create());

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_moderator(): void
    {
        $subject = new MergeCostumesRequest;
        $subject->setUserResolver(fn () => Trooper::factory()->asModerator()->create());

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_pass_for_two_different_costumes(): void
    {
        $validator = $this->validate(
            Costume::factory()->create()->id,
            Costume::factory()->create()->id,
        );

        $this->assertTrue($validator->passes());
    }

    public function test_rules_require_both_costumes(): void
    {
        $validator = $this->validate(null, null);

        $this->assertTrue($validator->errors()->has('source_costume_id'));
        $this->assertTrue($validator->errors()->has('target_costume_id'));
    }

    public function test_rules_reject_the_same_costume_twice(): void
    {
        $id = Costume::factory()->create()->id;

        $validator = $this->validate($id, $id);

        $this->assertTrue($validator->errors()->has('target_costume_id'));
    }

    public function test_rules_reject_soft_deleted_costumes(): void
    {
        $source = Costume::factory()->create();
        $target = Costume::factory()->create();
        $source->delete();
        $target->delete();

        $validator = $this->validate($source->id, $target->id);

        $this->assertTrue($validator->errors()->has('source_costume_id'));
        $this->assertTrue($validator->errors()->has('target_costume_id'));
    }

    public function test_rules_reject_merging_away_the_handler_costume(): void
    {
        $handler = Costume::factory()->create([Costume::NAME => Costume::HANDLER]);

        $validator = $this->validate($handler->id, Costume::factory()->create()->id);

        $this->assertTrue($validator->errors()->has('source_costume_id'));
    }

    private function validate(?int $source_id, ?int $target_id): \Illuminate\Validation\Validator
    {
        return Validator::make(
            ['source_costume_id' => $source_id, 'target_costume_id' => $target_id],
            (new MergeCostumesRequest)->rules(),
        );
    }
}
