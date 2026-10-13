<?php

declare(strict_types=1);

use App\Enums\PermissionRequestStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_permission_requests', function (Blueprint $table)
        {
            $table->id();

            $table->foreignId('trooper_id')
                ->constrained('tt_troopers')
                ->cascadeOnDelete();

            $table->foreignId('requester_trooper_id')
                ->nullable()
                ->constrained('tt_troopers')
                ->nullOnDelete();

            $table->foreignId('reviewer_trooper_id')
                ->nullable()
                ->constrained('tt_troopers')
                ->nullOnDelete();

            $table->string('membership_role', 16)->nullable();
            $table->json('moderated_organization_ids_to_add')->nullable();
            $table->json('moderated_organization_ids_to_remove')->nullable();
            $table->json('forum_groups_to_add')->nullable();
            $table->json('forum_groups_to_remove')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 16)->default(PermissionRequestStatus::PENDING->value);
            $table->text('denial_reason')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
            $table->trooperstamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_permission_requests');
    }
};
