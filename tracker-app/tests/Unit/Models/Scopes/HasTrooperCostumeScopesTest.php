<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class HasTrooperCostumeScopesTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_garrison_moderator_reviews_club_costume_of_garrison_member(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $pending = $this->costumeFor($trooper, $this->club);

        $subject = $this->moderatorOf($this->garrison);

        $this->assertTrue($pending->isReviewableBy($subject));
    }

    public function test_moderator_of_another_club_cannot_review(): void
    {
        $trooper = $this->memberOf($this->garrison);
        $this->memberOf($this->other_club, $trooper);
        $pending = $this->costumeFor($trooper, $this->club);

        $subject = $this->moderatorOf($this->other_club);

        $this->assertFalse($pending->isReviewableBy($subject));
    }

    public function test_moderator_who_does_not_cover_the_trooper_cannot_review(): void
    {
        $other_garrison = \App\Models\Organization::factory()->asRegion()->withParent($this->club)->create();
        $trooper = $this->memberOf($other_garrison);
        $pending = $this->costumeFor($trooper, $this->club);

        $subject = $this->moderatorOf($this->garrison);

        $this->assertFalse($pending->isReviewableBy($subject));
    }

    public function test_administrator_reviews_everything_but_their_own_costumes(): void
    {
        $subject = Trooper::factory()->asSystemOwner()->create();
        $trooper = $this->memberOf($this->garrison);
        $theirs = $this->costumeFor($trooper, $this->club);
        $own = $this->costumeFor($this->memberOf($this->garrison, $subject), $this->club, name: 'Jedi');

        $ids = TrooperCostume::onlyPending()->moderatedBy($subject)->pluck('id')->all();

        $this->assertSame([$theirs->id], $ids);
        $this->assertFalse($own->isReviewableBy($subject));
    }

    public function test_moderator_cannot_review_their_own_costume(): void
    {
        $subject = $this->moderatorOf($this->garrison);
        $this->memberOf($this->garrison, $subject);
        $own = $this->costumeFor($subject, $this->club);

        $this->assertFalse($own->isReviewableBy($subject));
    }
}
