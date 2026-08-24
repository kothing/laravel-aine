<?php

namespace App\Providers;

use Aine\Installer\Events\InstallerFinished;
use App\Events\ContentCreated;
use App\Events\ContentDeleted;
use App\Events\ContentPublished;
use App\Events\ContentRestored;
use App\Events\ContentTrashed;
use App\Events\ContentUnpublished;
use App\Events\ContentUpdated;
use App\Events\FormSubmitted;
use App\Listeners\CreateStorageLink;
use App\Listeners\FinalWebhookCallFailedListener;
use App\Listeners\ProcessWebhooks;
use App\Listeners\WebhookCallSucceededListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        ContentCreated::class => [
            ProcessWebhooks::class
        ],
        ContentUpdated::class => [
            ProcessWebhooks::class
        ],
        ContentTrashed::class => [
            ProcessWebhooks::class
        ],
        ContentDeleted::class => [
            ProcessWebhooks::class
        ],
        ContentPublished::class => [
            ProcessWebhooks::class
        ],
        ContentUnpublished::class => [
            ProcessWebhooks::class
        ],
        ContentRestored::class => [
            ProcessWebhooks::class
        ],
        FormSubmitted::class => [
            ProcessWebhooks::class
        ],

        // Runs `php artisan storage:link` as the final step of a fresh
        // install so seeded media is servable out of the box.
        InstallerFinished::class => [
            CreateStorageLink::class,
        ],

        WebhookCallSucceededEvent::class => [
            WebhookCallSucceededListener::class,
        ],
        FinalWebhookCallFailedEvent::class => [
            FinalWebhookCallFailedListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
