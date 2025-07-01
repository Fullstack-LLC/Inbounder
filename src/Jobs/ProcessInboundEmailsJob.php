<?php

declare(strict_types=1);

namespace Inbounder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Inbounder\Models\MailgunInboundEmail;
use Inbounder\Models\DistributionList;
use Carbon\Carbon;

class ProcessInboundEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $emails = MailgunInboundEmail::whereNull('processed_at')->get();
        foreach ($emails as $email) {
            $recipients = array_filter(array_map('trim', explode(',', $email->recipient)));
            foreach ($recipients as $recipient) {
                $list = DistributionList::where('email_address', $recipient)->first();
                if ($list) {
                    // You may want to pass more variables/options here
                    SendTemplatedEmailJob::dispatch($recipient, 'default-template', [], []);
                    Log::info('Dispatched SendTemplatedEmailJob for recipient', ['recipient' => $recipient, 'list_id' => $list->id]);
                }
            }
            $email->processed_at = Carbon::now();
            $email->save();
        }
    }
}
