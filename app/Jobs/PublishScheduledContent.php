<?php

namespace App\Jobs;

use App\Events\ContentPublished;
use App\Models\Content;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Delete the job if the content is no longer in the database.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(public Content $content)
    {
    }

    /**
     * Publish the content if its scheduled time has arrived.
     */
    public function handle(): void
    {
        // The job may be picked up later than expected: only publish when the
        // content is still scheduled and the time has actually arrived.
        if ($this->content->scheduled_at === null) {
            return;
        }

        $scheduledAt = Carbon::parse($this->content->scheduled_at);

        if ($scheduledAt->greaterThan(now())) {
            return;
        }

        $this->content->published_at = $scheduledAt;
        $this->content->scheduled_at = null;
        $this->content->save();

        ContentPublished::dispatch([
            'source' => 'Schedule',
            'content' => $this->content,
        ]);
    }
}
