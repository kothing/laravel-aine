<?php

use App\Events\ContentPublished;
use App\Models\Content;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;

/**
 * Publish content whose scheduled publish time has arrived.
 */
Artisan::command('aine:publish_scheduled', function () {
    $now = now();

    $due = Content::whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', $now)
        ->get();

    $count = 0;

    foreach ($due as $content) {
        $content->published_at = $content->scheduled_at;
        $content->scheduled_at = null;
        $content->save();

        ContentPublished::dispatch([
            'source' => 'Schedule',
            'content' => $content
        ]);

        $count++;
    }

    $this->info("Published {$count} scheduled content item(s).");
})->purpose('Publish content whose scheduled publish time has arrived');

/**
 * Run the scheduled publishing every minute.
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
    $user = User::create([
        'name' => $this->argument('name'),
        'email' => $this->argument('email'),
        'password' => Hash::make($this->argument('password'),)
    ]);

    $user->assignRole('super_admin');

    $this->info('New super admin created!');
    
})->describe('Create a new super admin account.');

Artisan::command('aine:refresh', function() {
    exec('rm ' . storage_path('logs/laravel*'));
    $this->info('Logs cleared!');

    exec('rm ' . storage_path('framework/sessions/*'));
    $this->info('Session files cleared!');

    Artisan::call('route:clear');
    $this->info('Route cache cleared!');

    Artisan::call('cache:clear');
    $this->info('Application cache cleared!');

    Artisan::call('config:clear');
    $this->info('Configuration cache cleared!');

    Artisan::call('view:clear');
    $this->info('Compiled views cleared!');

})->describe('Clear logs, sessions, route, cache, config and view');