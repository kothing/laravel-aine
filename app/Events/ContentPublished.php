<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ContentPublished
{
    use Dispatchable;

    /**
     * Content instance.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  array  $content
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getEventName(){
        return 'content.published';
    }

    public function getEventSource(){
        return $this->data['source'];
    }

    public function getEventContent(){
        return $this->data['content'];
    }
}
