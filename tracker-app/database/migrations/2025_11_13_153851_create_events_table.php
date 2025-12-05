<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tt_events', function (Blueprint $table)
        {
            $table->id();

            // Hosting Organization assignment
            $table->foreignId('organization_id')
                ->constrained('tt_organizations')
                ->cascadeOnDelete();

            //  Main Request details
            $table->foreignId('event_venue_id')
                ->constrained('tt_event_venues')
                ->cascadeOnDelete();

            // Event Shift
            $table->foreignId('main_event_id')
                ->nullable()
                ->constrained('tt_events')
                ->cascadeOnDelete();

            $table->boolean('is_shift')->default(false);

            // $table->integer('thread_id')->default(0);
            // $table->integer('post_id')->default(0);
            $table->string('name', 256);
            $table->string('type', 16)->default(EventType::REGULAR->value);
            $table->string('status', 16)->default(EventStatus::DRAFT->value);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();

            $table->boolean('limit_organizations')->default(false);
            $table->integer('troopers_allowed')->nullable();
            $table->integer('handlers_allowed')->nullable();

            $table->integer('charity_direct_funds')->default(0);
            $table->integer('charity_indirect_funds')->default(0);
            $table->string('charity_name')->nullable();
            $table->integer('charity_hours')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_events');
    }
};