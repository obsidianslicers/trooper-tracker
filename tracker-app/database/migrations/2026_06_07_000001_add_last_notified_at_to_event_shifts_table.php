<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_event_shifts', function (Blueprint $table)
        {
            $table->timestamp('last_notified_at')->nullable()->after('shift_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('tt_event_shifts', function (Blueprint $table)
        {
            $table->dropColumn('last_notified_at');
        });
    }
};
