<?php

declare(strict_types=1);

namespace Inbounder\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Inbounder\Models\EmailTemplate;
use Inbounder\Services\EmailTemplateService;

/**
 * Mailable class for sending templated emails.
 *
 * This class allows sending emails using predefined templates with variable substitution.
 */
class TemplatedEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The email template instance.
     */
    public EmailTemplate $template;

    /**
     * The variables for template rendering.
     */
    public array $variables;

    /**
     * The tags for Mailgun tracking.
     */
    private array $mailgunTags;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $templateSlug,
        array $variables = [],
        array $options = []
    ) {
        $templateService = app(EmailTemplateService::class);
        $rendered = $templateService->renderTemplate($templateSlug, $variables);

        $this->template = $rendered['template'];
        $this->variables = $variables;
        $this->mailgunTags = $options['tags'] ?? [];

        // Set the subject
        $this->subject($rendered['subject']);

        // Set additional options
        if (isset($options['from'])) {
            $this->from($options['from']['address'], $options['from']['name'] ?? null);
        }

        if (isset($options['reply_to'])) {
            $this->replyTo($options['reply_to']['address'], $options['reply_to']['name'] ?? null);
        }

        if (isset($options['attachments'])) {
            foreach ($options['attachments'] as $attachment) {
                if (isset($attachment['path'])) {
                    $this->attach($attachment['path'], $attachment['options'] ?? []);
                }
            }
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Configure the SwiftMailer message.
     */
    public function withSwiftMessage($message)
    {
        // Add Mailgun tags if present
        if (!empty($this->mailgunTags)) {
            $message->getHeaders()->addTextHeader('X-Mailgun-Tag', implode(',', $this->mailgunTags));
        }

        return $this;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->template->renderHtml($this->variables),
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
