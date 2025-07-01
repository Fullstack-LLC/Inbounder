<?php

declare(strict_types=1);

namespace Inbounder\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Inbounder\Mail\TemplatedEmail;

/**
 * Job to send a single templated email to a recipient.
 */
class SendTemplatedEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $recipient;

    public string $templateSlug;

    public array $variables;

    public array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(string $recipient, string $templateSlug, array $variables = [], array $options = [])
    {
        $this->recipient = $recipient;
        $this->templateSlug = $templateSlug;
        $this->variables = $variables;
        $this->options = $options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mailable = new TemplatedEmail($this->templateSlug, $this->variables, $this->options);
        Mail::to($this->recipient)->send($mailable);
    }
}
