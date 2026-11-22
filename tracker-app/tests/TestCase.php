<?php

namespace Tests;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected string $plugin_deploy_path = '';

    private bool $changed_schema = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Tells Laravel to ignore missing Vite assets
        $this->withoutVite();

        // Keep plugin deploy files out of the real storage folder.
        $this->plugin_deploy_path = sys_get_temp_dir().'/tt-plugin-deploy-'.uniqid();
        config(['plugins.deploy_path' => $this->plugin_deploy_path]);

        // MySQL commits the open transaction on any schema change (a plugin's migrations, a
        // test's Schema::table), so RefreshDatabase can't roll that test back: its rows,
        // tables and plugin records would stay for the tests after it. The next test then
        // starts from a freshly migrated database. SQLite rolls schema changes back.
        $this->changed_schema = false;
        DB::listen(function (QueryExecuted $query): void
        {
            if (preg_match('/^\s*(create|alter|drop|rename|truncate)\s/i', $query->sql) === 1)
            {
                $this->changed_schema = true;
                $this->reopenCommittedTransaction($query->connection);
            }
        });
    }

    protected function skipIfSqlite(): void
    {
        if (config('database.default') === 'sqlite')
        {
            $this->markTestSkipped('Requires MySQL JSON functions (JSON_CONTAINS / JSON_OVERLAPS).');
        }
    }

    /**
     * The query's SQL with identifiers quoted as SQLite quotes them, so assertions on
     * `"column" = ?` hold when the suite runs on MySQL, which quotes with backticks.
     */
    protected function sql(object $query): string
    {
        return str_replace('`', '"', $query->toSql());
    }

    /**
     * Laravel still counts the transaction MySQL just committed, so the test's own
     * DB::transaction() savepoints would fail. Open a real one again in its place.
     */
    private function reopenCommittedTransaction(Connection $connection): void
    {
        if ($connection->getDriverName() === 'mysql'
            && $connection->transactionLevel() > 0
            && !$connection->getPdo()->inTransaction())
        {
            $connection->getPdo()->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->plugin_deploy_path);

        if ($this->changed_schema && config('database.default') === 'mysql')
        {
            // Drop now, not at the next test's migrate:fresh: that test's app boots first,
            // reads the leftover plugin rows and would migrate those plugins' tables too.
            Schema::dropAllTables();
            RefreshDatabaseState::$migrated = false;
        }

        parent::tearDown();

        // echo memory_get_usage() . PHP_EOL;

        gc_collect_cycles();
    }
}
