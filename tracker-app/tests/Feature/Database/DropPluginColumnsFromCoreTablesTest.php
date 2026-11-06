<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Event;
use App\Models\Organization;
use App\Models\PermissionRequest;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class DropPluginColumnsFromCoreTablesTest extends TestCase
{
    use RefreshDatabase;

    private const FORUM_COLUMNS = [
        'related_forum',
        'related_forum_archive',
        'xenforo_group_active_id',
        'xenforo_group_reserve_id',
        'xenforo_group_retired_id',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject()->down();
    }

    public function test_up_drops_the_columns_when_they_hold_no_data(): void
    {
        Organization::factory()->create();

        $this->subject()->up();

        $this->assertFalse(Schema::hasColumn('tt_organizations', 'discord_mention'));
        $this->assertFalse(Schema::hasColumns('tt_organizations', self::FORUM_COLUMNS));
        $this->assertFalse(Schema::hasColumn('tt_events', 'thread_id'));
        $this->assertFalse(Schema::hasColumn('tt_permission_requests', 'forum_groups_to_add'));
    }

    public function test_up_stops_without_dropping_when_a_plugin_never_copied_the_data(): void
    {
        $organization = Organization::factory()->create();
        DB::table('tt_organizations')->where('id', $organization->id)
            ->update(['related_forum' => 9]);

        try
        {
            $this->subject()->up();
            $this->fail('The migration should have stopped.');
        }
        catch (RuntimeException $e)
        {
            $this->assertStringContainsString('plugins:enable xenforo', $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('tt_organizations', 'discord_mention'));
        $this->assertTrue(Schema::hasColumns('tt_organizations', self::FORUM_COLUMNS));
    }

    public function test_up_copies_rows_the_plugin_tables_are_missing_then_drops(): void
    {
        $this->createPluginTables();
        $copied = Organization::factory()->create();
        $missing = Organization::factory()->create();
        DB::table('tt_organizations')->whereIn('id', [$copied->id, $missing->id])
            ->update(['discord_mention' => '<@&501>', 'related_forum' => 9]);
        DB::table('tt_discord_organization_mentions')
            ->insert(['organization_id' => $copied->id, 'mention' => 'kept']);
        $event = Event::factory()->create();
        DB::table('tt_events')->where('id', $event->id)
            ->update(['thread_id' => 12, 'post_id' => 34]);
        $request = PermissionRequest::factory()->create();
        DB::table('tt_permission_requests')->where('id', $request->id)
            ->update(['forum_groups_to_add' => '[{"id":5,"title":"Staff"}]']);
        PermissionRequest::factory()->create();

        $this->subject()->up();

        $this->assertSame(
            ['kept', '<@&501>'],
            DB::table('tt_discord_organization_mentions')->orderBy('organization_id')
                ->pluck('mention')->all(),
        );
        $this->assertSame(2, DB::table('tt_xenforo_organization_forums')
            ->where('event_forum_id', 9)->count());
        $this->assertSame(34, (int) DB::table('tt_xenforo_event_threads')
            ->where('event_id', $event->id)->where('thread_id', 12)->value('post_id'));
        $this->assertSame(1, DB::table('tt_xenforo_permission_request_groups')->count());
        $this->assertFalse(Schema::hasColumn('tt_organizations', 'discord_mention'));
        $this->assertFalse(Schema::hasColumn('tt_events', 'thread_id'));
    }

    private function subject(): Migration
    {
        return require database_path(
            'migrations/2026_11_06_000001_drop_plugin_columns_from_core_tables.php'
        );
    }

    private function createPluginTables(): void
    {
        Schema::create('tt_discord_organization_mentions', function (Blueprint $table): void
        {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->string('mention');
            $table->timestamps();
        });
        Schema::create('tt_xenforo_organization_forums', function (Blueprint $table): void
        {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            foreach (['event_forum_id', 'archive_forum_id', 'active_group_id',
                'reserve_group_id', 'retired_group_id'] as $column)
            {
                $table->unsignedBigInteger($column)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('tt_xenforo_event_threads', function (Blueprint $table): void
        {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->boolean('should_create_thread');
            $table->unsignedBigInteger('thread_id')->nullable();
            $table->unsignedBigInteger('post_id')->nullable();
            $table->timestamps();
        });
        Schema::create('tt_xenforo_permission_request_groups', function (Blueprint $table): void
        {
            $table->id();
            $table->unsignedBigInteger('permission_request_id');
            $table->json('groups_to_add')->nullable();
            $table->json('groups_to_remove')->nullable();
            $table->timestamps();
        });
    }
}
