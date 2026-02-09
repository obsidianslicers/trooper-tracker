<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tt_trooper_costumes')) {
            Schema::table('tt_trooper_costumes', function (Blueprint $table) {
                if (! Schema::hasColumn('tt_trooper_costumes', 'costume_prefix')) {
                    $table->string('costume_prefix')->nullable()->after('costume_id');
                }
                if (! Schema::hasColumn('tt_trooper_costumes', 'small_image_url')) {
                    $table->string('small_image_url')->nullable()->after('costume_prefix');
                }
                if (! Schema::hasColumn('tt_trooper_costumes', 'large_image_url')) {
                    $table->string('large_image_url')->nullable()->after('small_image_url');
                }
                if (! Schema::hasColumn('tt_trooper_costumes', 'bucket_off_url')) {
                    $table->string('bucket_off_url')->nullable()->after('large_image_url');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tt_trooper_costumes')) {
            Schema::table('tt_trooper_costumes', function (Blueprint $table) {
                if (Schema::hasColumn('tt_trooper_costumes', 'bucket_off_url')) {
                    $table->dropColumn('bucket_off_url');
                }
                if (Schema::hasColumn('tt_trooper_costumes', 'large_image_url')) {
                    $table->dropColumn('large_image_url');
                }
                if (Schema::hasColumn('tt_trooper_costumes', 'small_image_url')) {
                    $table->dropColumn('small_image_url');
                }
                if (Schema::hasColumn('tt_trooper_costumes', 'costume_prefix')) {
                    $table->dropColumn('costume_prefix');
                }
            });
        }
    }
};
