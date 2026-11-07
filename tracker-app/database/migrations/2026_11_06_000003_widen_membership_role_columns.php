<?php

declare(strict_types=1);

use App\Enums\MembershipRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes room for the event_coordinator role, which doesn't fit in 16 characters.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->resize(32);
    }

    /**
     * Fails rather than truncates while any row still holds a longer role.
     */
    public function down(): void
    {
        $this->resize(16);
    }

    private function resize(int $length): void
    {
        Schema::table('tt_troopers', function (Blueprint $table) use ($length): void
        {
            $table->string('membership_role', $length)
                ->default(MembershipRole::MEMBER->value)
                ->change();
        });

        Schema::table('tt_permission_requests', function (Blueprint $table) use ($length): void
        {
            $table->string('membership_role', $length)->nullable()->change();
        });
    }
};
