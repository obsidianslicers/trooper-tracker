<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Event types move from a PHP enum to a table administrators manage. Events keep
 * their `type` column, which now holds an event type's slug.
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

            $table->string('slug', 32)->unique();
            $table->string('name', 64);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_counted')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->trooperstamps();
        });

        $this->seedDefaults();
        $this->addMissingTypes();

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->string('type', 32)->default('regular')->change();
            $table->foreign('type')->references('slug')->on('tt_event_types');
        });
    }

    public function down(): void
    {
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->dropForeign(['type']);
        });

        Schema::dropIfExists('tt_event_types');
    }

    private function seedDefaults(): void
    {
        $now = now();
        $rows = [];

        foreach (self::DEFAULTS as $index => [$slug, $name, $description])
        {
            $rows[] = [
                'slug' => $slug,
                'name' => $name,
                'description' => $description,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tt_event_types')->insert($rows);
    }

    /**
     * Keeps the foreign key from failing on a value the enum never had.
     */
    private function addMissingTypes(): void
    {
        $known = array_column(self::DEFAULTS, 0);

        $unknown = DB::table('tt_events')
            ->whereNotIn('type', $known)
            ->distinct()
            ->pluck('type');

        foreach ($unknown as $offset => $slug)
        {
            DB::table('tt_event_types')->insert([
                'slug' => $slug,
                'name' => ucfirst((string) $slug),
                'sort_order' => count($known) + $offset + 1,
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
