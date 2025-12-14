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

            // $table->integer('thread_id')->default(0);
            // $table->integer('post_id')->default(0);
            $table->string('name', 256);
            $table->string('type', 16)->default(EventType::REGULAR->value);
            $table->string('status', 16)->default(EventStatus::DRAFT->value);

            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();

            $table->integer('troopers_allowed')->nullable();
            $table->integer('handlers_allowed')->nullable();

            $table->integer('charity_direct_funds')->default(0);
            $table->integer('charity_indirect_funds')->default(0);
            $table->string('charity_name')->nullable();
            $table->integer('charity_hours')->nullable();

            // Contact info
            $table->string('contact_name', 128)->nullable();
            $table->string('contact_phone', 128)->nullable();
            $table->string('contact_email', 128)->nullable();

            // Event details from request
            $table->string('venue', 256)->nullable();
            $table->string('venue_address', 256)->nullable();
            $table->string('venue_city', 128)->nullable();
            $table->string('venue_state', 128)->nullable();
            $table->string('venue_zip', 128)->nullable();
            $table->string('venue_country', 128)->nullable();

            $table->dateTime('event_start')->nullable();
            $table->dateTime('event_end')->nullable();
            $table->string('event_website', 512)->nullable();

            // Request specifics
            $table->integer('expected_attendees')->nullable();
            $table->integer('requested_characters')->nullable();
            $table->text('requested_character_types')->nullable();

            // Venue amenities / permissions
            $table->boolean('secure_staging_area')->default(false);
            $table->boolean('allow_blasters')->default(false);
            $table->boolean('allow_props')->default(false);
            $table->boolean('parking_available')->default(false);
            $table->boolean('accessible')->default(false);
            $table->text('amenities')->nullable();

            // Misc
            $table->string('referred_by', 1024)->nullable();
            $table->text('source')->nullable();

            $table->text('comments')->nullable();

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