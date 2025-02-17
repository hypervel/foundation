<?php

declare(strict_types=1);

namespace LaravelHyperf\Foundation\Testing;

use LaravelHyperf\Foundation\Testing\Traits\CanConfigureMigrationCommands;

trait DatabaseMigrations
{
    use CanConfigureMigrationCommands;

    /**
     * Define hooks to migrate the database before and after each test.
     */
    public function runDatabaseMigrations(): void
    {
        $this->command('migrate:fresh', $this->migrateFreshUsing());

        $this->beforeApplicationDestroyed(function () {
            $this->command('migrate:rollback');

            RefreshDatabaseState::$migrated = false;
        });
    }
}
