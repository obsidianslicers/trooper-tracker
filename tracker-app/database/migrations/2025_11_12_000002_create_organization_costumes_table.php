<?php

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
        Schema::create('tt_organization_costumes', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('tt_organizations')
                ->cascadeOnDelete();

            $table->string('name', 128);
            $table->dateTime('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->unique(['organization_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_organization_costumes');
    }
};
