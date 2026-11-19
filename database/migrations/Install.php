<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Database\Migrations;

use CraftCms\Cms\Database\Migration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use JustinHoltWeb\Stethoscope\Stethoscope;
use Laravel\Pulse\Facades\Pulse;
use Throwable;

/**
 * Pulse's tables belong to Pulse, not Stethoscope. Install creates them with
 * Pulse's own migration when the app doesn't have them yet; uninstall purges
 * only Stethoscope's entries and leaves the tables (and Pulse's own data) for
 * Pulse, which stays installed until the package is removed.
 */
class Install extends Migration
{
    public function up(): void
    {
        if (self::pulseTablesExist()) {
            return;
        }

        $migration = require dirname((string) (new \ReflectionClass(Pulse::class))->getFileName(), 3).'/database/migrations/2023_06_07_000001_create_pulse_tables.php';
        $migration->up();
    }

    public function down(): void
    {
        if (! self::pulseTablesExist()) {
            return;
        }

        // A busy site keeps writing to these tables: retry a lock timeout rather than leave data behind.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                Pulse::purge(Stethoscope::TYPES);

                return;
            } catch (Throwable $e) {
                Log::warning("Stethoscope couldn't purge its Pulse entries (attempt {$attempt}): {$e->getMessage()}");
                usleep(500_000 * $attempt);
            }
        }
    }

    private static function pulseTablesExist(): bool
    {
        $schema = Schema::connection(Config::get('pulse.storage.database.connection'));

        return $schema->hasTable('pulse_entries') && $schema->hasTable('pulse_aggregates') && $schema->hasTable('pulse_values');
    }
}
