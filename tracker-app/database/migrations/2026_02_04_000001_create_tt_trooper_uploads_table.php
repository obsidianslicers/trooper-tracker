<?php

declare(strict_types=1);

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
        if (! Schema::hasTable('tt_trooper_uploads')) {
            Schema::create('tt_trooper_uploads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')
                    ->constrained('tt_organizations')
                    ->onDelete('cascade');

                $table->string('identifier', 64);
                $table->string('prefix')->nullable();
                $table->string('costume_name');
                $table->string('small_image_url')->nullable();
                $table->string('large_image_url')->nullable();
                $table->string('bucket_off_url')->nullable();

                $table->timestamps();
                $table->softDeletes();

                // Audit columns (nullable trooper IDs who created/updated/deleted)
                $table->unsignedBigInteger('created_id')->nullable()->index();
                $table->unsignedBigInteger('updated_id')->nullable()->index();
                $table->unsignedBigInteger('deleted_id')->nullable()->index();

                $table->index(['organization_id', 'identifier'], 'trooper_uploads_org_identifier_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_trooper_uploads');
    }
};
