<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Event types move from a PHP enum to a table administrators manage. Each event's
 * `type` string becomes `event_type_id`, a foreign key to the type.
 */
return new class extends Migration {
    /**
     * The former enum cases in the order the event form listed them (alphabetical),
     * except Regular goes first: the form preselects the first type, and new
     * events defaulted to Regular before.
     */
    private const DEFAULTS = [
        ['regular', 'Regular', 'Standard costuming event.'],
        ['armorparty', 'Armor Party', 'Social gathering for armor building and maintenance.'],
        ['birthdayparty', 'Birthday Party', 'Private birthday party appearance.'],
        ['charity', 'Charity', 'Charitable fundraising event.'],
        ['convention', 'Convention', 'Convention or expo appearance.'],
        ['disney', 'Disney', 'Disney-related event or appearance.'],
        ['hospital', 'Hospital', 'Hospital visit or patient interaction.'],
        ['lucasfilm', 'Lucas Film Limited', 'Lucasfilm/official Star Wars event.'],
        ['other', 'Other', 'Any other kind of event.'],
        ['publicrelations', 'Public Relations', 'Public relations or promotional appearance.'],
        ['virtualtroop', 'Virtual Troop', 'Online or remote virtual event.'],
        ['wedding', 'Wedding', 'Private wedding appearance.'],
    ];

    public function up(): void
    {
        Schema::create('tt_event_types', function (Blueprint $table)
        {
            $table->id();

            $table->string('name', 64);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_counted')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->trooperstamps();
        });

        $ids = $this->seedDefaults() + $this->addMissingTypes();

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->foreignId('event_type_id')->nullable()->after('type')->constrained('tt_event_types');
        });

        foreach ($ids as $value => $id)
        {
            DB::table('tt_events')->where('type', $value)->update(['event_type_id' => $id]);
        }

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->unsignedBigInteger('event_type_id')->nullable(false)->change();
            $table->dropColumn('type');
        });
    }

    /**
     * Puts the type strings back: a seeded type by its name, any other as its name in
     * lowercase without spaces (as the enum spelled them).
     */
    public function down(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->string('type', 32)->default('regular')->after('name');
        });

        $values = array_column(self::DEFAULTS, 0, 1);

        foreach (DB::table('tt_event_types')->pluck('name', 'id') as $id => $name)
        {
            DB::table('tt_events')->where('event_type_id', $id)->update([
                'type' => $values[$name] ?? Str::limit(Str::slug((string) $name, ''), 32, '') ?: 'other',
            ]);
        }

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->dropConstrainedForeignId('event_type_id');
        });

        Schema::dropIfExists('tt_event_types');
    }

    /**
     * @return array<string, int> the new type's id for each former enum value
     */
    private function seedDefaults(): array
    {
        $now = now();
        $ids = [];

        foreach (self::DEFAULTS as $index => [$value, $name, $description])
        {
            $ids[$value] = DB::table('tt_event_types')->insertGetId([
                'name' => $name,
                'description' => $description,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    /**
     * Gives a value the enum never had a hidden type of its own, so no event is left without one.
     *
     * @return array<string, int>
     */
    private function addMissingTypes(): array
    {
        $known = array_column(self::DEFAULTS, 0);
        $ids = [];

        $unknown = DB::table('tt_events')
            ->whereNotIn('type', $known)
            ->distinct()
            ->pluck('type');

        foreach ($unknown as $offset => $value)
        {
            $ids[$value] = DB::table('tt_event_types')->insertGetId([
                'name' => ucfirst((string) $value),
                'sort_order' => count($known) + $offset + 1,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }
};
