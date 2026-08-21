<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Enums\MembershipRole;
use App\Messages\Troopers\Commands\CreateTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_trooper_with_guardian_and_hashed_password(): void
    {
        $guardian = Trooper::factory()->asMember()->create([
            Trooper::EMAIL => 'guardian@example.com',
        ]);

        $subject = new CreateTrooper(
            legal_name: 'Jane Doe',
            display_name: 'Jane',
            email: 'jane@example.com',
            membership_role: MembershipRole::MEMBER,
            password: 'secret123',
            phone: '404-555-1111',
            date_of_birth: '2000-01-01',
            guardian_email: 'guardian@example.com',
        );

        $result = $subject->handle();

        $this->assertInstanceOf(Trooper::class, $result);
        $this->assertSame('Jane Doe', $result->legal_name);
        $this->assertSame('Jane', $result->display_name);
        $this->assertSame('jane@example.com', $result->email);
        $this->assertSame('404-555-1111', $result->phone);
        $this->assertSame(MembershipRole::MEMBER, $result->membership_role);
        $this->assertSame($guardian->id, $result->guardian_id);
        $this->assertNotNull($result->setup_completed_at);
        $this->assertTrue(Hash::check('secret123', $result->password));

        $this->assertDatabaseHas('tt_troopers', [
            Trooper::LEGAL_NAME => 'Jane Doe',
            Trooper::DISPLAY_NAME => 'Jane',
            Trooper::EMAIL => 'jane@example.com',
            Trooper::PHONE => '4045551111',
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER->value,
            Trooper::GUARDIAN_ID => $guardian->id,
            Trooper::DATE_OF_BIRTH => '2000-01-01 00:00:00',
        ]);
    }
}
