<?php

namespace Aine\Installer\Helpers;

use Exception;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;

class DatabaseManager
{
    /**
     * Migrate and seed the database.
     *
     * @return array
     */
    public function migrateAndSeed()
    {
        $outputLog = new BufferedOutput;

        $this->sqlite($outputLog);

        return $this->migrate($outputLog);
    }

    /**
     * Run the migration and call the seeder.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function migrate(BufferedOutput $outputLog)
    {
        try {
            Artisan::call('migrate', ['--force'=> true], $outputLog);
            
            try {
                Artisan::call('storage:link');
            } catch (\Throwable $e) {
                // handled below via the existence check
            }

            $target = storage_path('app/public');
            $link = public_path('storage');
            // Remove a dangling symlink (target missing) so it can be recreated.
            if (is_link($link) && ! file_exists($link)) {
                @unlink($link);
            }
            if (! file_exists($link) && function_exists('symlink')) {
                try {
                    @symlink($target, $link);
                } catch (\Throwable $e2) {
                    // ignore; the fallback route covers this
                }
            }
            if (! file_exists($link) && ! is_link($link)) {
                $outputLog->write('storage:link skipped: public/storage symlink could not be created — the /storage fallback route will serve media files.', 1);
            }

            $other_commands = config('installer.artisan_command');
            if (!empty($other_commands)) {
                config(['installer.seed_demo_skip_admin' => true]);
                foreach ($other_commands as $key => $value) {
                    Artisan::call($key, $value, $outputLog);
                }
            }

            // Normalize permissions on the whole public storage tree created
            // while seeding. The process umask often leaves directories at
            // 700, which makes web servers / PHP-FPM running under another
            // user return 403/404 for media and thumbnails. Normalize so
            // every file and directory under storage/app/public is servable
            // right after install, without any manual chmod.
            $publicRoot = storage_path('app/public');
            if (is_dir($publicRoot)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($publicRoot, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $item) {
                    @chmod($item->getPathname(), $item->isDir() ? 0775 : 0664);
                }
            }
        } catch (\Throwable $e) {
            return $this->response($e->getMessage(), 'error', $outputLog);
        }

        return $this->response(trans('installer_messages.final.finished'), 'success', $outputLog);
    }

    /**
     * Return a formatted error messages.
     *
     * @param string $message
     * @param string $status
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     * @return array
     */
    private function response($message, $status, BufferedOutput $outputLog)
    {
        return [
            'status' => $status,
            'message' => $message,
            'dbOutputLog' => $outputLog->fetch(),
        ];
    }

    /**
     * Check database type. If SQLite, then create the database file.
     *
     * @param \Symfony\Component\Console\Output\BufferedOutput $outputLog
     */
    private function sqlite(BufferedOutput $outputLog)
    {
        if (DB::connection() instanceof SQLiteConnection) {
            $database = DB::connection()->getDatabaseName();
            if ($database && ! str_starts_with($database, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $database)) {
                $database = base_path($database);
            }
            if (! file_exists($database)) {
                $dir = dirname($database);
                if (! is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                touch($database);
                DB::reconnect(Config::get('database.default'));
            }
            $outputLog->write('Using SqlLite database: '.$database, 1);
        }
    }
}
