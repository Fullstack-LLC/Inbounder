<?php

declare(strict_types=1);

namespace Inbounder\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Inbounder\Models\EmailTemplate;
use Inbounder\Models\MailgunOutboundEmail;
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
     * The outbound email instance.
     */
    public MailgunOutboundEmail $outboundEmail;

    /**
     * The variables for template rendering.
     */
    public array $variables;

    /**
     * The message ID.
     */
    public string $outboundMessageId;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $user,
        MailgunOutboundEmail $outboundEmail,
        EmailTemplate $template,
        array $variables = [],
        array $options = []
    ) {
        $templateService = app(EmailTemplateService::class);

        $rendered = $templateService->renderTemplate($user, $template, $outboundEmail, $variables);

        $this->template = $template;
        $this->variables = $variables;
        $this->outboundMessageId = $outboundEmail->message_id;

        // Set the subject
        $this->subject($outboundEmail->subject);

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
            tags: ['outbound'],
            metadata: [
                'tenant' => 'fullstackllc',
                'outbound_message_id' => $this->outboundMessageId,
            ],
        );
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
