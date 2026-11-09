<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional forums per event type. When set, they win over the hosting organization's
 * event and archive forums.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_event_type_xenforo_forums', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('event_type_id')
                ->unique()
                ->constrained('tt_event_types')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('event_forum_id')->nullable();
            $table->unsignedBigInteger('archive_forum_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_event_type_xenforo_forums');
    }
};
