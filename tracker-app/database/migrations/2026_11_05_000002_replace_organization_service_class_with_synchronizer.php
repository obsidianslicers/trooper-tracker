<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clubs now store the key of a registered club synchronizer instead of a PHP class
 * name, so plugins can offer synchronizers too.
 */
return new class extends Migration {
    private const KEYS = [
        'App\Services\Synchronizers\TheLegionService' => '501st',
        'App\Services\Synchronizers\RebelLegionService' => 'rebel-legion',
        'App\Services\Synchronizers\MandalorianMercsService' => 'mandalorian-mercs',
        'App\Services\Synchronizers\DarkEmpireServices' => 'dark-empire',
        'App\Services\Synchronizers\DroidBuildersService' => 'droid-builders',
        'App\Services\Synchronizers\SaberGuildServices' => 'saber-guild',
    ];

    public function up(): void
    {
        Schema::table('tt_organizations', function ($table): void
        {
            $table->string('synchronizer', 64)->nullable()->after('image_path_sm');
        });

        foreach (self::KEYS as $class => $key)
        {
            DB::table('tt_organizations')
                ->where('service_class', $class)
                ->update(['synchronizer' => $key]);
        }

        Schema::table('tt_organizations', function ($table): void
        {
            $table->dropColumn('service_class');
        });
    }

    public function down(): void
    {
        Schema::table('tt_organizations', function ($table): void
        {
            $table->string('service_class', 128)->nullable()->after('image_path_sm');
        });

        foreach (self::KEYS as $class => $key)
        {
            DB::table('tt_organizations')
                ->where('synchronizer', $key)
                ->update(['service_class' => $class]);
        }

        Schema::table('tt_organizations', function ($table): void
        {
            $table->dropColumn('synchronizer');
        });
    }
};
