<?php

use App\Jobs\PublishScheduledContent;
use App\Models\Content;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

/**
 * Dispatch publish jobs for content whose scheduled publish time has arrived.
 *
 * Due items are fetched in chunks and each one is dispatched to the queue,
 * so a large backlog never blocks the scheduler process for long.
 */
Artisan::command('aine:publish_scheduled', function () {
    $now = now();

    $count = 0;

    Content::query()
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', $now)
        ->chunkById(100, function ($contents) use (&$count) {
            foreach ($contents as $content) {
                PublishScheduledContent::dispatch($content);
                $count++;
            }
        });

    $this->info("Dispatched {$count} scheduled content item(s) for publishing.");
})->purpose('Dispatch publish jobs for content whose scheduled publish time has arrived');

/**
 * Run the scheduled publishing every minute.
 *
 * ⚠️  PRODUCTION CRON REQUIRED — this schedule will NOT run on its own.
 * Add the following crontab entry on every production server, otherwise
 * content whose `scheduled_at` has passed will never be published:
 *
 *   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
 *
 * See README.md "Production deployment → Task scheduler" for full setup.
 */
Schedule::call(function () {
    Artisan::call('aine:publish_scheduled');
})->everyMinute()->name('publish-scheduled-content')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/
Artisan::command('aine:create_super {name} {email} {password}', function(){
    $email = strtolower($this->argument('email'));
    $password = $this->argument('password');

    // Email must be unique — fail clearly instead of throwing a DB
    // unique-constraint exception, and point the user at the existing account.
    if (User::where('email', $email)->exists()) {
        $this->error("A user with email '{$email}' already exists.");
        return Command::FAILURE;
    }

    // Reject empty / too-short passwords up front so the created account is
    // not trivially guessable.
    if (strlen($password) < 8) {
        $this->error('Password must be at least 8 characters.');
        return Command::FAILURE;
    }

    // Ensure the super_admin role exists (seeder normally creates it, but the
    // command may run before seeding on a fresh install).
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);

    $user = User::create([
        'name' => $this->argument('name'),
        'email' => $email,
        'password' => Hash::make($password),
    ]);

    $user->assignRole($role);

    $this->info('New super admin created!');

})->describe('Create a new super admin account.');

Artisan::command('aine:refresh', function() {
    // Logs and session files are only cleared outside production: wiping
    // them on a live server would destroy audit trails and active sessions.
    if (app()->isProduction()) {
        $this->warn('Production environment detected - skipping log and session cleanup.');
    } else {
        foreach (File::glob(storage_path('logs/*.log')) ?: [] as $logFile) {
            File::delete($logFile);
        }
        $this->info('Log files cleared!');

        if (is_dir($sessionsPath = storage_path('framework/sessions'))) {
            foreach (File::files($sessionsPath) as $sessionFile) {
                File::delete($sessionFile);
            }
        }
        $this->info('Session files cleared!');
    }

    Artisan::call('route:clear');
    $this->info('Route cache cleared!');

    Artisan::call('cache:clear');
    $this->info('Application cache cleared!');

    Artisan::call('config:clear');
    $this->info('Configuration cache cleared!');

    Artisan::call('view:clear');
    $this->info('Compiled views cleared!');
})->describe('Clear logs, sessions, route, cache, config and view');
