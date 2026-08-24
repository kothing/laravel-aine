<?php

namespace App\Listeners;

use App\Models\WebhookLog;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class WebhookCallSucceededListener
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
     * @param  WebhookCallSucceededEvent  $event
     * @return void
     */
    public function handle(WebhookCallSucceededEvent $event)
    {
        $log = [
            'project_uuid' => $event->payload['project_id'],
            'webhook_id' => $event->payload['webhook_id'],
            'action' => $event->payload['action'],
            'url' => $event->webhookUrl,
            'status' => 'success',
            'request' => json_encode($event->payload),
            'response' => $event->response->getBody()->getContents(),
        ];
        WebhookLog::create($log);
    }
}
