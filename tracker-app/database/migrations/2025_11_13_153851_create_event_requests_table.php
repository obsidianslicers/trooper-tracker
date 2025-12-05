<?php

use App\Enums\EventStatus;
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
        Schema::create('tt_event_requests', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('tt_events')
                ->cascadeOnDelete();

            // Contact info
            $table->string('contact_name', 128)->nullable();
            $table->string('contact_phone', 128)->nullable();
            $table->string('contact_email', 128)->nullable();

            // Event details from request
            $table->string('event_name', 256)->nullable();
            $table->string('venue', 256)->nullable();
            $table->string('venue_address', 256)->nullable();
            $table->string('venue_city', 128)->nullable();
            $table->string('venue_state', 128)->nullable();
            $table->string('venue_zip', 128)->nullable();
            $table->string('venue_country', 128)->nullable();

            $table->dateTime('event_start')->nullable();
            $table->dateTime('event_end')->nullable();
            $table->string('event_website', 256)->nullable();

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
            $table->text('comments')->nullable();
            $table->string('referred_by')->nullable();

            $table->text('source')->nullable();

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
        Schema::dropIfExists('tt_event_requests');
    }
};