<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationNamesTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    private const TABLES = [
        'tt_xenforo_event_threads',
        'tt_xenforo_organization_forums',
        'tt_xenforo_permission_request_groups',
    ];

    public function test_index_names_fit_mysql_identifier_limit(): void
    {
        $this->enableXenforo();

        foreach (self::TABLES as $table)
        {
            foreach (Schema::getIndexes($table) as $index)
            {
                $this->assertLessThanOrEqual(64, strlen($index['name']), $index['name']);
            }
        }
    }

    public function test_permission_request_groups_migration_reruns_after_a_partial_run(): void
    {
        $this->enableXenforo();
        $subject = require base_path('plugins/xenforo/database/migrations/'
            .'2026_11_05_000003_create_xenforo_permission_request_groups_table.php');

        $subject->up();

        $this->assertTrue(Schema::hasTable('tt_xenforo_permission_request_groups'));
    }
}
