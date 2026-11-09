<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Renames the stored administrator role to system_owner, including past role changes in the
 * audit trail so the history shows one value.
 */
return new class extends Migration {
    public function up(): void
    {
        $this->rename('administrator', 'system_owner');
    }

    public function down(): void
    {
        $this->rename('system_owner', 'administrator');
    }

    private function rename(string $from, string $to): void
    {
        DB::table('tt_troopers')
            ->where('membership_role', $from)
            ->update(['membership_role' => $to]);

        DB::table('tt_permission_requests')
            ->where('membership_role', $from)
            ->update(['membership_role' => $to]);

        foreach (['old_value', 'new_value'] as $column)
        {
            DB::table('tt_model_changes')
                ->where('field_name', 'membership_role')
                ->where($column, $from)
                ->update([$column => $to]);
        }
    }
};
