<?php

use App\Enums\AwardFrequency;
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
        Schema::create('tt_awards', function (Blueprint $table)
        {
            $table->id();

            // Organization assignment
            $table->foreignId('organization_id')
                ->constrained('tt_organizations')
                ->cascadeOnDelete();

            $table->string('name', 128);
            $table->string('frequency', 16)->default(AwardFrequency::ONCE->value);
            $table->boolean('has_multiple_recipients')->default(false);

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            // Prevent duplicate entries
            $table->unique(['name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_awards');
    }
};
