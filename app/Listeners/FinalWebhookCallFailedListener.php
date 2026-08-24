<?php

namespace App\Listeners;

use App\Models\WebhookLog;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;

class FinalWebhookCallFailedListener
{
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
     * @param  FinalWebhookCallFailedEvent  $event
     * @return void
     */
    public function handle(FinalWebhookCallFailedEvent $event)
    {
        $log = [
            'project_uuid' => $event->payload['project_id'],
            'webhook_id' => $event->payload['webhook_id'],
            'action' => $event->payload['action'],
            'url' => $event->webhookUrl,
            'status' => 'failed',
            'request' => json_encode($event->payload),
            'response' => $event->response != null ? $event->response->getBody()->getContents() : '',
        ];
        WebhookLog::create($log);
    }
}
