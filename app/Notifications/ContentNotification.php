<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ContentNotification extends Notification
{
    use Queueable;

    /**
     * @param array{action: string, entity_label: string, project_id: int|null, collection_id: int|null, content_id: int|null} $payload
     */
    public function __construct(public array $payload)
    {
    }

    /**
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * @param mixed $notifiable
     * @return array
     */
    public function toDatabase($notifiable): array
    {
        return $this->payload;
    }
}
