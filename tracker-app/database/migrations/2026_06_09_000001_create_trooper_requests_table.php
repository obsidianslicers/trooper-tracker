<?php

declare(strict_types=1);

use App\Enums\TrooperRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_trooper_requests', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->foreignId('organization_id')
                ->constrained('tt_organizations')
                ->cascadeOnDelete();

            $table->foreignId('primary_organization_id')
                ->constrained('tt_organizations')
                ->cascadeOnDelete();

            $table->string('identifier', 64)->nullable();
            $table->string('status', 16)->default(TrooperRequestStatus::PENDING->value);
            $table->text('denial_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->index('trooper_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_trooper_requests');
    }
};
