<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tt_push_notifications');
    }

    public function down(): void
    {
        // tt_push_notifications replaced by tt_notifications (Laravel database channel)
    }
};
