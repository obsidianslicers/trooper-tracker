<?php

declare(strict_types=1);

namespace Database\Seeders\States;

use App\Models\Trooper;
use App\Enums\MembershipStatus;
use App\Models\TrooperOrganization;
use App\Models\Organization;
use App\Models\TrooperAssignment;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates a 
 * 
 */
class VisitingTroopersState extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->createActiveVisitor();
        $this->createExpiredVisitor();
        $this->createRenewingVisitor();
    }

    private function createActiveVisitor(): void
    {
        $visitor = Trooper::factory()->asVisitor()->create();

        $visitor->visitor_expires_at = now()->addDays(7);
        $visitor->save();

        $this->createVisitor($visitor);
    }

    private function createExpiredVisitor(): void
    {
        $visitor = Trooper::factory()->asVisitor()->create();

        $visitor->visitor_expires_at = now()->subDays(7);
        $visitor->save();

        $this->createVisitor($visitor);
    }

    private function createRenewingVisitor(): void
    {
        $visitor = Trooper::factory()->asVisitor()->create();

        $visitor->visitor_expires_at = now()->subDays(7);
        $visitor->membership_status = MembershipStatus::PENDING;
        $visitor->save();

        $this->createVisitor($visitor);
    }

    private function createVisitor(Trooper $visitor): void
    {

        $the_legion = Organization::where(Organization::NAME, '501st Legion')->first();

        if (!$the_legion)
        {
            throw new RuntimeException('No 501st Legion organization found.');
        }

        TrooperOrganization::factory()
            ->forTrooper($visitor)
            ->forOrganization($the_legion)
            ->withIdentifier("VISITOR#{$visitor->id}")
            ->create();

        TrooperAssignment::factory()
            ->asMember()
            ->forTrooper($visitor)
            ->forOrganization($the_legion)
            ->create();
    }
}
