<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves each event's forum thread out of core's tt_events table into a table
 * the XenForo plugin owns.
 *
 * Linked threads and the "do not create a thread" choices are copied here. The
 * plugin never changes core tables: tt_events.thread_id, post_id and
 * create_forum_thread are left alone and are dropped later by a core migration.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_xenforo_event_threads', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('event_id')
                ->unique()
                ->constrained('tt_events')
                ->cascadeOnDelete();
            $table->boolean('should_create_thread')->default(true);
            $table->unsignedBigInteger('thread_id')->nullable()->index();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->timestamps();
        });

        $this->copyExistingThreads();
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_xenforo_event_threads');
    }

    private function copyExistingThreads(): void
    {
        if (!Schema::hasColumn('tt_events', 'thread_id'))
        {
            return;
        }

        DB::table('tt_events')
            ->select(['id', 'thread_id', 'post_id', 'create_forum_thread'])
            ->where(fn ($query) => $query->whereNotNull('thread_id')
                ->orWhere('create_forum_thread', false))
            ->orderBy('id')
            ->chunk(500, function (Collection $events): void
            {
                $now = now();

                DB::table('tt_xenforo_event_threads')->insert(
                    $events->map(fn (object $event): array => [
                        'event_id' => $event->id,
                        'should_create_thread' => (bool) ($event->create_forum_thread ?? true),
                        'thread_id' => $event->thread_id ?: null,
                        'post_id' => $event->post_id ?: null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }
};
