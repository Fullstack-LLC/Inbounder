<?php

declare(strict_types=1);

namespace Inbounder\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inbounder\Models\MailgunInboundEmail;
use Inbounder\Models\DistributionList;
use Inbounder\Models\EmailTemplate;
use Carbon\Carbon;

class ProcessInboundEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $queue = $this->getConfig('mailgun.queue.default_queue');

        if ($this->getConfig('mailgun.queue.enabled')) {
            $queue = $this->getConfig('mailgun.queue.queues.inbound_emails');
        }

        $emails = MailgunInboundEmail::whereNull('processed_at')->get();

        foreach ($emails as $email) {

            $recipients = array_filter(array_map('trim', explode(',', $email->recipient)));

            foreach ($recipients as $recipient) {

                $list = null;
                $template = null;

                // Get the Distribution List for the recipient. If it doesn't exist, skip the email.
                $list = DistributionList::where('inbound_email_address', $recipient)->first();

                if (! $list) {
                    logger()->notice('Distribution List not found for recipient: ' . $recipient . '. Skipping email.');
                    return;
                }

                $subscribers = $list->activeSubscribers()->get();

                foreach ($subscribers as $subscriber) {

                    $user = User::where('id', $subscriber->user_id)->first();

                    //SendTemplatedEmailJob::dispatch($email, $user, $list);
                }
            }
            $email->processed_at = Carbon::now();
            $email->save();
        }
    }

    /**
     * Get a config value with proper fallback.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getConfig(string $key, $default = null)
    {
        return config($key, $default);
    }
}
