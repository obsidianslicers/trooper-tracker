<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tt_troopers', function (Blueprint $table)
        {
            $table->boolean('is_untrusted')->default(false)->after('deletion_requested_at');
            $table->string('untrusted_reason', 512)->nullable()->after('is_untrusted');
            $table->timestamp('untrusted_at')->nullable()->after('untrusted_reason');
            $table->unsignedBigInteger('untrusted_id')->nullable()->after('untrusted_at');
        });

        Schema::table('tt_event_troopers', function (Blueprint $table)
        {
            $table->json('requested_organization_ids')->nullable()->after('costume_organization_ids');
            $table->timestamp('verification_requested_at')->nullable()->after('requested_organization_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tt_event_troopers', function (Blueprint $table)
        {
            $table->dropColumn(['requested_organization_ids', 'verification_requested_at']);
        });

        Schema::table('tt_troopers', function (Blueprint $table)
        {
            $table->dropColumn(['is_untrusted', 'untrusted_reason', 'untrusted_at', 'untrusted_id']);
        });
    }
};
