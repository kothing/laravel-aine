<?php

namespace App\Aine;

use App\Http\Resources\ContentResource;
use App\Models\Collection;
use App\Models\Project;
use App\Models\Webhook;
use Spatie\WebhookServer\WebhookCall;

class WebhookHelper
{
    public static function processWebhooks($content, $event, $source)
    {

        $collection_id = $content['collection_id'];
        $webhooks = Webhook::where('status', 1)
                            ->whereJsonContains('events', $event)
                            ->whereJsonContains('sources', $source)
                            ->whereHas('collections', function ($query) use ($collection_id) {
                                $query->where('collection_id', $collection_id);
                            })->get();

        foreach ($webhooks as $wh) {
            $webhookCall = WebhookCall::create();
            $webhookCall->url($wh->url);

            if ($wh->secret === null) {
                $webhookCall->doNotSign();
            } else {
                $webhookCall->useSecret($wh->secret);
            }

            if ($wh->payload) {
                $project = Project::find($content['project_id']);
                $collection = Collection::find($content['collection_id']);

                // Guard against race conditions where the project or collection
                // may have been deleted between the content event and webhook
                // dispatch (e.g. when using a queue driver).
                if (! $project || ! $collection) {
                    continue;
                }

                $payload = [
                    'action' => $event,
                    'source' => $source,
                    'project_id' => $project->uuid,
                    'collection' => $collection->name,
                    'collection_slug' => $collection->slug,
                    'webhook_id' => $wh->id,
                ];

                if ($event == 'content.deleted') {
                    $payload['item_id'] = $content['item_id'];
                } else {
                    $payload['item'] = json_decode(json_encode(new ContentResource($content)));
                }

                if ($event == 'form.submitted') {
                    $payload['form_id'] = $content['form_id'];
                }

                $webhookCall->payload($payload);
            }

            $webhookCall->dispatch();
        }
    }
}
