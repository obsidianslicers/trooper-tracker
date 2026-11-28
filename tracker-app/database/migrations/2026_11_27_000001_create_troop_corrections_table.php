<?php

declare(strict_types=1);

use App\Enums\TroopCorrectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A trooper's request for credit for a troop the tracker didn't record: an event that
 * exists, but they never signed up for it. The claimed credit waits on an event_troopers
 * row in Awaiting Verification; this row is the record of who asked, what they said and
 * who decided.
 *
 * previous_status is the sign-up's status before the request (cancelled, no show, ...);
 * null when the request made the sign-up, so turning it down removes it again.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_troop_corrections', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->foreignId('event_id')
                ->constrained('tt_events')
                ->cascadeOnDelete();

            $table->foreignId('event_trooper_id')
                ->constrained('tt_event_troopers')
                ->cascadeOnDelete();

            $table->string('previous_status', 32)->nullable();
            $table->text('details');

            $table->foreignId('coordinator_trooper_id')
                ->nullable()
                ->constrained('tt_troopers')
                ->nullOnDelete();

            $table->string('status', 32)->default(TroopCorrectionStatus::AWAITING_COORDINATOR->value);

            $table->foreignId('decided_by_trooper_id')
                ->nullable()
                ->constrained('tt_troopers')
                ->nullOnDelete();

            $table->timestamp('decided_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('coordinator_denied_at')->nullable();
            $table->timestamp('escalated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_troop_corrections');
    }
};
