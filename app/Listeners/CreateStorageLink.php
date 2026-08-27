<?php

namespace App\Listeners;

use Aine\Installer\Events\InstallerFinished;
use Illuminate\Support\Facades\Log;

/**
 * Create the public/storage symlink as the final step of a fresh install.
 *
 * The installer seeds a demo project whose media files live under
 * storage/app/public/<project-uuid>/. Linking public/storage to that
 * directory makes those images immediately servable by the web server, so
 * the site works out of the box — no manual `php artisan storage:link` is
 * needed after the wizard finishes.
 */
class CreateStorageLink
{
    /**
     * Handle the install-finished event.
     *
     * @param  InstallerFinished  $event
     * @return void
     */
    public function handle(InstallerFinished $event): void
    {
        $target = storage_path('app/public');
        $link = public_path('storage');

        // Make sure the link target directory exists (a fresh clone may not
        // ship storage/app/public), otherwise the symlink would dangle.
        if (! is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        // A dangling symlink (is_link but file_exists fails) would make the
        // symlink() call report "already exists"; drop it first.
        if (is_link($link) && ! file_exists($link)) {
            @unlink($link);
        }

        // A usable link (or real directory) already exists: nothing to do.
        if (file_exists($link)) {
            return;
        }

        // Try direct symlink() first — avoids the `exec()` requirement that
        // Artisan::call('storage:link') brings, which breaks on hosts that
        // disable exec() via php.ini.
        try {
            if (@symlink($target, $link)) {
                return; // Success — nothing more to do.
            }
        } catch (\Throwable $e) {
            Log::warning('symlink() failed during install: '.$e->getMessage());
        }

        // Fallback: try the Artisan command as a last resort (needs exec()).
        try {
            $exitCode = \Illuminate\Support\Facades\Artisan::call('storage:link');
            if ($exitCode !== 0) {
                Log::warning('storage:link returned exit code '.$exitCode.' during install.');
            }
        } catch (\Throwable $e) {
            // Best effort only: some hosts disable both symlink() and exec().
            // Media still works through the storage/{path} route, so never
            // fail the install for this.
            Log::warning('storage:link failed during install: '.$e->getMessage());
        }
    }
}
