<?php

namespace App\Listeners;

use App\Aine\WebhookHelper;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Offloads webhook processing to the queue so that outbound HTTP calls never
 * block the primary request cycle.
 *
 * When QUEUE_CONNECTION=sync (local dev) the job still runs inline — that is
 * intentional for development simplicity. In production (database/redis) the
 * listener is queued, the request returns immediately, and the queue worker
 * handles webhook delivery asynchronously.
 */
class ProcessWebhooks implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event): void
    {
        $eventName = $event->getEventName();
        $eventSource = $event->getEventSource();
        $eventContent = $event->getEventContent();

        WebhookHelper::processWebhooks($eventContent, $eventName, $eventSource);
    }
}
