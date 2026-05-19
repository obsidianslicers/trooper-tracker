<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table) {
            $table->boolean('is_visitor')->default(false)->after('push_notifications_enabled');
            $table->datetime('visitor_expires_at')->nullable()->after('is_visitor');
            $table->datetime('visitor_notified_at')->nullable()->after('visitor_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table) {
            $table->dropColumn(['is_visitor', 'visitor_expires_at', 'visitor_notified_at']);
        });
    }
};
