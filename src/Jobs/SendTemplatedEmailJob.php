<?php

declare(strict_types=1);

namespace Inbounder\Jobs;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Inbounder\Mail\TemplatedEmail;
use Inbounder\Models\DistributionList;
use Inbounder\Models\EmailTemplate;
use Inbounder\Models\MailgunInboundEmail;
use Inbounder\Models\MailgunOutboundEmail;

/**
 * Job to send a single templated email to a recipient.
 */
class SendTemplatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;

    public DistributionList $list;

    public MailgunInboundEmail $email;

    public array $variables;

    public array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        MailgunInboundEmail $email,
        User $user,
        DistributionList $list,
        array $variables = [],
        array $options = []
    )
    {
        $this->email = $email;
        $this->user = $user;
        $this->list = $list;
        $this->variables = $variables;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $template = $this->list->emailTemplate;

        if (! $template) {
            logger()->notice('No template found for list: ' . $this->list->id . '. Skipping email...');
            return;
        }

        $options = [
            'from' => [
                'name' => $this->getFromName(),
                'address' => $this->getReplyToAddress(),
            ],
            'reply_to' => [
                'name' => $this->getFromName(),
                'address' => $this->getReplyToAddress(),
            ],
        ];

        // Merge options with the default options
        $options = array_merge($options, $this->options);

        $mailable = new TemplatedEmail($template->slug, $this->variables, $options);

        Mail::to($this->user->email)->send($mailable);

        MailgunOutboundEmail::create([
            'message_id' => uniqid('outbound_' . time() . '_', true),
            'recipient' => $this->user->email,
            'from_address' => $this->getReplyToAddress(),
            'from_name' => $this->getFromName(),
            'distribution_list_id' => $this->list->id,
            'email_template_id' => $this->list->email_template_id,
            'user_id' => $this->user->id,

            'subject' => $mailable->subject,
            'sent_at' => Carbon::now(),
            'status' => 'sent',
            'metadata' => [
                'variables' => $this->variables,
                'options' => $this->options,
            ],
        ]);
    }

    /**
     * If the list type is 'list', return the outbound email address.
     */
    private function getReplyToAddress(): string
    {
        if ($this->list->list_type === 'list') {
            return $this->list->outbound_email_address;
        }

        return $this->email->sender;
    }

    private function getFromName(): string
    {
        if ($this->list->list_type === 'list') {
            return $this->list->name;
        }

        return $this->user->name;
    }
}
