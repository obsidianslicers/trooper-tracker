<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Messages\Costumes\Commands\Merge\MergeCostumes;
use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class MergeCostumesSubmitControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_invoke_merges_source_into_target_and_redirects(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $source = Costume::factory()->create([Costume::NAME => 'TK Stormtroper']);
        $target = Costume::factory()->create([Costume::NAME => 'TK Stormtrooper']);

        $messages = $this->fakeMessages([MergeCostumes::class => null]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('admin.costumes.merge'), [
                'source_costume_id' => $source->id,
                'target_costume_id' => $target->id,
            ]);

        $response->assertRedirect(route('admin.costumes.merge'));
        $response->assertSessionHas('success', 'Merged "TK Stormtroper" into "TK Stormtrooper".');
        $messages->assertCalled(
            MergeCostumes::class,
            fn (array $params): bool => $params['source_costume']->id === $source->id
                && $params['target_costume']->id === $target->id,
        );
    }

    public function test_invoke_runs_the_real_merge(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $source = Costume::factory()->create();
        $target = Costume::factory()->create();

        $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('admin.costumes.merge'), [
                'source_costume_id' => $source->id,
                'target_costume_id' => $target->id,
            ]);

        $this->assertDatabaseMissing('tt_costumes', [Costume::ID => $source->id]);
        $this->assertDatabaseHas('tt_costumes', [Costume::ID => $target->id]);
    }

    public function test_invoke_rejects_merging_a_costume_into_itself(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create();

        $messages = $this->fakeMessages([MergeCostumes::class => null]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('admin.costumes.merge'), [
                'source_costume_id' => $costume->id,
                'target_costume_id' => $costume->id,
            ]);

        $response->assertSessionHasErrors('target_costume_id');
        $this->assertSame([], $messages->callsTo(MergeCostumes::class));
    }

    public function test_invoke_forbids_moderators(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $source = Costume::factory()->create();
        $target = Costume::factory()->create();

        $messages = $this->fakeMessages([MergeCostumes::class => null]);

        $this->actingAs($moderator)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('admin.costumes.merge'), [
                'source_costume_id' => $source->id,
                'target_costume_id' => $target->id,
            ]);

        $this->assertSame([], $messages->callsTo(MergeCostumes::class));
        $this->assertDatabaseHas('tt_costumes', [Costume::ID => $source->id]);
    }
}
