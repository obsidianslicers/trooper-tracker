<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table)
        {
            $table->boolean('push_notifications_enabled')->default(true)->after('notification_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table)
        {
            $table->dropColumn('push_notifications_enabled');
        });
    }
};
