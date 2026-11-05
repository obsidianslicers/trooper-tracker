<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A plugin an administrator turned on before its Composer packages were
 * installed. The next deploy installs them and then turns it on.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tt_plugins', function (Blueprint $table): void
        {
            $table->boolean('is_enable_pending')->default(false)->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tt_plugins', function (Blueprint $table): void
        {
            $table->dropColumn('is_enable_pending');
        });
    }
};
