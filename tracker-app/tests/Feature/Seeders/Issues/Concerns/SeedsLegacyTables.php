<?php

declare(strict_types=1);

namespace Tests\Feature\Seeders\Issues\Concerns;

use App\Enums\EventTrooperStatus;
use App\Enums\MembershipStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal TT1.0 legacy tables (troopers, events, event_sign_up, costumes) plus the named clubs
 * HasClubMaps requires, for testing the credit repair seeders.
 */
trait SeedsLegacyTables
{
    private const array CLUB_NAMES = [
        '501st Legion',
        'Rebel Legion',
        'Mandalorian Mercs',
        'Droid Builders',
        'Saber Guild',
        'Dark Empire',
    ];

    /** @var array<string, Organization> */
    protected array $clubs = [];

    protected function seedLegacyWorld(): void
    {
        $this->createLegacyTrooperTable();
        $this->createLegacyCostumeTable();
        $this->createLegacyEventTable();
        $this->createLegacySignupTable();

        foreach (self::CLUB_NAMES as $name)
        {
            $this->clubs[$name] = Organization::factory()
                ->asOrganization()
                ->withName($name)
                ->create();
        }
    }

    /** @param  array<int, string>  $club_names */
    protected function assertCredit(array $club_names, EventTrooper $event_trooper): void
    {
        $this->assertEqualsCanonicalizing(
            $this->clubIds(...$club_names),
            $event_trooper->refresh()->costume_organization_ids,
        );
    }

    protected function club(string $name): Organization
    {
        return $this->clubs[$name];
    }

    protected function clubId(string $name): int
    {
        return $this->clubs[$name]->id;
    }

    /** @return array<int, int> */
    protected function clubIds(string ...$names): array
    {
        return array_map(fn (string $name) => $this->clubId($name), $names);
    }

    protected function regionOf(string $club_name, ?string $region_name = null): Organization
    {
        return Organization::factory()
            ->asRegion()
            ->withParent($this->club($club_name))
            ->withName($region_name ?? "{$club_name} Region")
            ->create();
    }

    protected function createLegacyTrooperTable(): void
    {
        Schema::create('troopers', function (Blueprint $table): void
        {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name')->default('');
            $table->integer('squad')->default(0);
            $table->integer('permissions')->default(0);

            foreach (['p501', 'pRebel', 'pDroid', 'pMando', 'pOther', 'pSG', 'pDE'] as $column)
            {
                $table->integer($column)->default(0);
            }

            foreach (['tkid', 'forum_id', 'rebelforum', 'mandoid', 'sgid', 'de_id'] as $column)
            {
                $table->string($column)->nullable();
            }

            foreach ([0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 13] as $squad)
            {
                $table->integer("esquad{$squad}")->default(0);
            }
        });
    }

    protected function createLegacyCostumeTable(): void
    {
        Schema::create('costumes', function (Blueprint $table): void
        {
            $table->increments('id');
            $table->string('costume');
            $table->integer('club')->nullable();
        });
    }

    /** Every column EventSeeder::overlayEvent() reads, nullable or defaulted. */
    protected function createLegacyEventTable(): void
    {
        Schema::create('events', function (Blueprint $table): void
        {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('link')->default(0);
            $table->string('name')->default('Legacy Event');
            $table->dateTime('dateStart');
            $table->dateTime('dateEnd')->nullable();
            $table->integer('squad')->default(0);
            $table->integer('label')->default(0);
            $table->boolean('closed')->default(true);
            $table->boolean('limitedEvent')->default(false);
            $table->boolean('allowTentative')->default(false);
            $table->text('comments')->default('');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $this->addLegacyEventDetailColumns($table);
        });
    }

    private function addLegacyEventDetailColumns(Blueprint $table): void
    {
        $this->addColumns($table, 'integer', [
            'limitRebels', 'limit501st', 'limitMando', 'limitDroid', 'limitOther', 'limitSG',
            'limitDE', 'limitTotalTroopers', 'limitHandlers',
        ], default: 500);
        $this->addColumns($table, 'string', [
            'venue', 'website', 'requestedCharacter', 'amenities', 'referred', 'location',
            'charityName', 'charityNote',
        ]);
        $this->addColumns($table, 'integer', [
            'numberOfAttend', 'requestedNumber', 'secureChanging', 'blasters', 'lightsabers',
            'parking', 'mobility', 'charityDirectFunds', 'charityIndirectFunds',
            'charityAddHours', 'thread_id', 'post_id',
        ]);
    }

    protected function createLegacySignupTable(): void
    {
        Schema::create('event_sign_up', function (Blueprint $table): void
        {
            $table->increments('id');
            $table->unsignedBigInteger('trooperid');
            $table->unsignedBigInteger('troopid');
            $table->integer('costume')->default(0);
            $table->integer('costume_backup')->default(0);
            $table->integer('status')->default(3);
            $table->integer('addedby')->default(0);
            $table->string('note')->nullable();
            $table->dateTime('signuptime')->nullable();
        });
    }

    /** @param  array<int, string>  $columns  nullable when no default is given */
    private function addColumns(
        Blueprint $table,
        string $type,
        array $columns,
        ?int $default = null,
    ): void {
        foreach ($columns as $column)
        {
            $definition = $table->{$type}($column);

            if ($default === null)
            {
                $definition->nullable();
            }
            else
            {
                $definition->default($default);
            }
        }
    }

    /** @param  array<string, mixed>  $attributes  legacy columns, e.g. ['p501' => 1] */
    protected function legacyTrooper(Trooper $trooper, array $attributes = []): void
    {
        DB::table('troopers')->insert(array_merge([
            'id' => $trooper->id,
            'name' => $trooper->display_name ?? 'Legacy Trooper',
        ], $attributes));
    }

    protected function legacyCostume(string $name, ?int $club): int
    {
        return DB::table('costumes')->insertGetId(['costume' => $name, 'club' => $club]);
    }

    protected function legacyEvent(int $id, Carbon $starts_at, int $link = 0): void
    {
        DB::table('events')->insertOrIgnore([
            'id' => $id,
            'link' => $link,
            'dateStart' => $starts_at->format('Y-m-d H:i:s'),
            'dateEnd' => $starts_at->copy()->addHours(2)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Records a TT1.0 signup for the shift. The legacy event row shares the tt shift's id, the
     * same 1:1 mapping the import preserved.
     */
    protected function legacySignup(
        EventShift $shift,
        Trooper $trooper,
        int $costume_id,
        ?int $legacy_shift_id = null,
    ): void {
        if ($legacy_shift_id === null)
        {
            $legacy_shift_id = $shift->id;
            $this->legacyEvent($shift->id, $shift->shift_starts_at);
        }

        DB::table('event_sign_up')->insert([
            'trooperid' => $trooper->id,
            'troopid' => $legacy_shift_id,
            'costume' => $costume_id,
            'signuptime' => $shift->shift_starts_at->copy()->subWeek()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function shiftAt(string $starts_at): EventShift
    {
        $start = Carbon::parse($starts_at);

        return EventShift::factory()
            ->forEvent(Event::factory()->create())
            ->withShiftStartsAt($start)
            ->withShiftEndsAt($start->copy()->addHours(2))
            ->create();
    }

    /** @param  array<string, mixed>  $attributes */
    protected function attended(
        Trooper $trooper,
        EventShift $shift,
        array $attributes = [],
    ): EventTrooper {
        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->create(array_merge([
                EventTrooper::STATUS => EventTrooperStatus::ATTENDED,
                EventTrooper::COSTUME_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => null,
                EventTrooper::ORGANIZATION_ID => null,
            ], $attributes));
    }

    /** @param  array<int, int>|null  $org_ids */
    protected function attendedWithCredit(
        Trooper $trooper,
        string $starts_at,
        ?array $org_ids,
    ): EventTrooper {
        return $this->attended($trooper, $this->shiftAt($starts_at), [
            EventTrooper::COSTUME_ORGANIZATION_IDS => $org_ids,
        ]);
    }

    /**
     * An attended row backed by a TT1.0 signup in the given legacy costume.
     *
     * @param  array<int, int>|null  $org_ids
     */
    protected function tt1Row(
        Trooper $trooper,
        string $starts_at,
        string $costume,
        ?int $club,
        ?array $org_ids = null,
    ): EventTrooper {
        $shift = $this->shiftAt($starts_at);
        $this->legacySignup($shift, $trooper, $this->legacyCostume($costume, $club));

        return $this->attended($trooper, $shift, [
            EventTrooper::COSTUME_ORGANIZATION_IDS => $org_ids,
        ]);
    }

    protected function membershipSince(
        Trooper $trooper,
        Organization $club,
        string $created_at,
        MembershipStatus $status = MembershipStatus::ACTIVE,
        ?string $identifier = null,
    ): TrooperOrganization {
        return TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($club)
            ->withMembershipStatus($status)
            ->create([
                TrooperOrganization::IDENTIFIER => $identifier,
                TrooperOrganization::JOIN_DATE => null,
                TrooperOrganization::CREATED_AT => Carbon::parse($created_at),
                TrooperOrganization::UPDATED_AT => Carbon::parse($created_at),
            ]);
    }

    protected function memberAssignmentSince(
        Trooper $trooper,
        Organization $organization,
        string $created_at,
    ): TrooperAssignment {
        return TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create([
                TrooperAssignment::CREATED_AT => Carbon::parse($created_at),
                TrooperAssignment::UPDATED_AT => Carbon::parse($created_at),
            ]);
    }
}
