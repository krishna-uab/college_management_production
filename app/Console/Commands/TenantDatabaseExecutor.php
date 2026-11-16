<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class TenantDatabaseExecutor extends Command
{
    protected $signature = 'tenants:migrate {action=migrate}';
    protected $description = 'Run migrations for all tenant databases (every connection in config/database.php except default)';

    /**
     * Connections jinhe skip karna hai.
     */
    protected array $skipConnections = ['default','sqlite','mysql','mariadb','pgsql','sqlsrv'];

    public function handle()
    {
        $action = $this->argument('action');
        $path = 'database/migrations/TenantDatabase';

        if (!in_array($action, ['migrate', 'rollback', 'fresh','status'], true)) {
            $this->error('Invalid action: ' . $action . ' (allowed: migrate, rollback, fresh, status)');
            return self::FAILURE;
        }

        // config/database.php se saare connections, default ko chhodkar
        $connections = array_keys(config('database.connections', []));
        $connections = array_values(array_diff($connections, $this->skipConnections));

        if (empty($connections)) {
            $this->warn('No tenant connections found in config/database.php.');
            return self::SUCCESS;
        }

        $this->info('Action      : ' . $action);
        $this->info('Connections : ' . implode(', ', $connections));

        $totalStart = microtime(true);
        $success = 0;
        $failed = [];

        foreach ($connections as $connectionName) {
            $this->newLine();
            $this->info('===============================');
            $this->info('Processing connection: ' . $connectionName);

            $start = microtime(true);

            try {
                $connection = DB::connection($connectionName);
                $connection->getPdo();

                $expectedDb = config("database.connections.{$connectionName}.database");
                $connectedDb = $connection->getDatabaseName();

                $this->info('Connected DB: ' . $connectedDb);

                if ($connectedDb !== $expectedDb) {
                    throw new \RuntimeException(
                        'Database mismatch. Expected: ' . $expectedDb . ', Connected: ' . $connectedDb
                    );
                }

                $options = [
                    '--database' => $connectionName,
                    '--path' => $path,
                    '--force' => true,
                ];

                switch ($action) {
                    case 'rollback':
                        Artisan::call('migrate:rollback', $options);
                        break;
                    case 'fresh':
                        Artisan::call('migrate:fresh', $options);
                        break;
                    default:
                        Artisan::call('migrate', $options);
                }

                $this->line(Artisan::output());

                $elapsed = round(microtime(true) - $start, 2);
                $this->info("Done: {$connectionName} ({$connectedDb}) in {$elapsed}s");
                $success++;
            } catch (\Throwable $e) {
                $elapsed = round(microtime(true) - $start, 2);
                $this->error("Failed: {$connectionName} after {$elapsed}s");
                $this->error($e->getMessage());
                $failed[] = $connectionName;
            } finally {
                DB::disconnect($connectionName);
            }
        }

        $totalElapsed = round(microtime(true) - $totalStart, 2);

        $this->newLine();
        $this->info('===============================');
        $this->info('Tenant migration process completed.');
        $this->info("Success : {$success}");
        $this->info('Failed  : ' . count($failed) . (count($failed) ? ' (' . implode(', ', $failed) . ')' : ''));
        $this->info("Total time: {$totalElapsed}s");

        return empty($failed) ? self::SUCCESS : self::FAILURE;
    }
}

// for migrate
// php artisan tenants:migrate

// for rollback
// php artisan tenants:migrate rollback

// for fresh
// php artisan tenants:migrate fresh
