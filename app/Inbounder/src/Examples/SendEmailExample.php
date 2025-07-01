<?php

declare(strict_types=1);

namespace Inbounder\Examples;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Inbounder\Services\MailgunEmailSender;

/**
 * Example demonstrating how to send tracked emails using the Mailgun tracking system.
 */
class SendEmailExample
{
    /**
     * Send a simple email using the default Mailgun configuration.
     */
    public function sendSimpleEmail(): void
    {
        Mail::to('recipient@example.com')
            ->send(new WelcomeEmail);
    }

    /**
     * Send an email with custom from address.
     */
    public function sendEmailWithCustomFrom(): void
    {
        $email = new WelcomeEmail;
        $email->from('custom@your-domain.com', 'Custom Name');

        Mail::to('recipient@example.com')
            ->send($email);
    }

    /**
     * Send a tracked email with campaign tracking.
     */
    public function sendTrackedEmail(): void
    {
        $emailSender = app(MailgunEmailSender::class);

        $trackingData = [
            'campaign_id' => 'welcome-campaign-2024',
            'user_id' => 'user-123',
            'template_name' => 'welcome-email',
            'subject' => 'Welcome to Our App!',
            'metadata' => [
                'source' => 'registration',
                'user_type' => 'new',
            ],
        ];

        $messageId = $emailSender->sendWithTracking(
            'recipient@example.com',
            new WelcomeEmail,
            $trackingData
        );

        // You can now track this email using the message ID
        if ($messageId) {
            $email = $emailSender->getEmailByMessageId($messageId);
            // Use the email record for tracking
        }
    }

    /**
     * Send a campaign email with tracking.
     */
    public function sendCampaignEmail(): void
    {
        $emailSender = app(MailgunEmailSender::class);

        $trackingData = [
            'campaign_id' => 'newsletter-jan-2024',
            'template_name' => 'monthly-newsletter',
            'subject' => 'January Newsletter',
            'metadata' => [
                'newsletter_type' => 'monthly',
                'month' => 'january',
            ],
        ];

        $messageId = $emailSender->sendWithTracking(
            'subscriber@example.com',
            new NewsletterEmail,
            $trackingData
        );
    }

    /**
     * Get campaign statistics.
     */
    public function getCampaignStats(): array
    {
        $emailSender = app(MailgunEmailSender::class);

        return $emailSender->getCampaignStats('welcome-campaign-2024');
    }

    /**
     * Get user email statistics.
     */
    public function getUserStats(): array
    {
        $emailSender = app(MailgunEmailSender::class);

        return $emailSender->getUserStats('user-123');
    }

    /**
     * Send an email to multiple recipients with tracking.
     */
    public function sendToMultipleRecipients(): void
    {
        $emailSender = app(MailgunEmailSender::class);

        $trackingData = [
            'campaign_id' => 'bulk-update-2024',
            'template_name' => 'bulk-notification',
            'subject' => 'Important Update',
        ];

        $recipients = ['user1@example.com', 'user2@example.com'];

        foreach ($recipients as $recipient) {
            $trackingData['user_id'] = 'user-'.md5($recipient);

            $messageId = $emailSender->sendWithTracking(
                $recipient,
                new BulkEmail,
                $trackingData
            );
        }
    }
}

/**
 * Example Mailable class.
 */
class WelcomeEmail extends Mailable
{
    public function build()
    {
        return $this->view('emails.welcome')
            ->subject('Welcome to Our App!');
    }
}

/**
 * Example Newsletter Mailable class.
 */
class NewsletterEmail extends Mailable
{
    public function build()
    {
        return $this->view('emails.newsletter')
            ->subject('Monthly Newsletter');
    }
}

/**
 * Example Bulk Mailable class.
 */
class BulkEmail extends Mailable
{
    public function build()
    {
        return $this->view('emails.bulk')
            ->subject('Important Update');
    }
}
