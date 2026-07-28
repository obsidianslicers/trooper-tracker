<?php

use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
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
        Event::query()
            ->lazyById(100)
            ->each(function (Event $event)
            {
                $formatted_address = $this->buildAddress($event);

                if (!empty($formatted_address))
                {
                    $event->updateQuietly([
                        Event::VENUE_ADDRESS => $formatted_address,
                    ]);
                }
            });

        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->dropColumn([
                'venue_city',
                'venue_state',
                'venue_zip',
                'venue_country',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original schema columns (Data consolidation is irreversible)
        Schema::table('tt_events', function (Blueprint $table)
        {
            $table->string('venue_city', 128)->nullable();
            $table->string('venue_state', 128)->nullable();
            $table->string('venue_zip', 128)->nullable();
            $table->string('venue_country', 128)->nullable();
        });
    }

    /**
     * Build a comma-separated geocoding address string from event venue fields.
     *
     * Constructs an address by combining venue address, city, state, zip, and country fields.
     * Avoids duplicating address components that are already included in the base address.
     *
     * @param  Event  $event  The event instance to build the address from.
     * @return string The formatted address string suitable for geocoding services.
     */
    private function buildAddress(Event $event): string
    {
        $address = trim($event->venue_address ?? '');

        if (empty($address))
        {
            return $address;
        }

        // Normalize the base address for duplicate detection
        $base = strtolower($event->venue_address ?? '');

        // Append city if not already included
        if (!empty($event->venue_city) && !str_contains($base, strtolower($event->venue_city)))
        {
            $address .= ' ' . $event->venue_city;
        }

        // Append state if not already included
        if (!empty($event->venue_state) && !str_contains($base, strtolower($event->venue_state)))
        {
            $address .= ', ' . $event->venue_state;
        }

        // Append ZIP if not already included
        if (!empty($event->venue_zip) && !str_contains($base, strtolower($event->venue_zip)))
        {
            $address .= ' ' . $event->venue_zip;
        }

        // Append country if not already included
        if (!empty($event->venue_country) && !str_contains($base, strtolower($event->venue_country)))
        {
            $address .= ' ' . $event->venue_country;
        }

        // address city, state zip country 
        return trim($address);
    }
};