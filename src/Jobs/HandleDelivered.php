<?php

namespace Fullstack\Inbounder\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Spatie\WebhookClient\Models\WebhookCall;

class HandleDelivered
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Bind the implementation.
     */
    protected WebhookCall $webhookCall;

    /**
     * Create new Job.
     */
    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle() {}
}
