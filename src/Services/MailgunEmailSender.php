<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inbounder\Exceptions\MailgunTrackingException;

/**
 * Service for sending tracked emails through Mailgun.
 *
 * This service extends Laravel's mail functionality to automatically
 * track outbound emails and link them to webhook events.
 */
class MailgunEmailSender
{
    /**
     * The tracking service instance.
     */
    private MailgunTrackingService $trackingService;

    /**
     * Create a new MailgunEmailSender instance.
     */
    public function __construct(MailgunTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Send a tracked email.
     *
     * @param  string|array  $to
     * @return string|null The message ID if tracking is enabled, null otherwise
     *
     * @throws \Inbounder\Exceptions\MailgunTrackingException
     */
    public function send($to, Mailable $mailable, array $trackingData = []): ?string
    {
        // Send the email using Laravel's mail system
        $response = Mail::to($to)->send($mailable);

        // Extract message ID from the response (this is Mailgun-specific)
        $messageId = $this->extractMessageId($response);

        // Track the email if enabled and we have a message ID
        if ($messageId && config('mailgun.database.outbound.enabled', false)) {
            $this->trackEmail($messageId, $to, $mailable, $trackingData);
        }

        return $messageId;
    }

    /**
     * Send a tracked email with custom headers for tracking.
     *
     * @param  string|array  $to
     * @return string|null The message ID if tracking is enabled, null otherwise
     *
     * @throws \Inbounder\Exceptions\MailgunTrackingException
     */
    public function sendWithTracking($to, Mailable $mailable, array $trackingData = []): ?string
    {
        // Add tracking headers to the mailable
        $this->addTrackingHeaders($mailable, $trackingData);

        return $this->send($to, $mailable, $trackingData);
    }

    /**
     * Extract message ID from Mailgun response.
     *
     * @param  mixed  $response
     */
    private function extractMessageId($response): ?string
    {
        // This is a simplified implementation
        // In a real scenario, you might need to parse the Mailgun response
        // or use a custom mail driver that returns the message ID

        // For now, we'll generate a unique ID that can be used for tracking
        // In production, you'd want to get the actual Mailgun message ID
        return 'msg_'.uniqid().'@'.config('mailgun.domain');
    }

    /**
     * Add tracking headers to the mailable.
     */
    private function addTrackingHeaders(Mailable $mailable, array $trackingData): void
    {
        // Add custom headers for tracking
        if (isset($trackingData['campaign_id'])) {
            $mailable->withSwiftMessage(function ($message) use ($trackingData) {
                $message->getHeaders()->addTextHeader('X-Campaign-ID', $trackingData['campaign_id']);
            });
        }

        if (isset($trackingData['user_id'])) {
            $mailable->withSwiftMessage(function ($message) use ($trackingData) {
                $message->getHeaders()->addTextHeader('X-User-ID', $trackingData['user_id']);
            });
        }

        if (isset($trackingData['template_name'])) {
            $mailable->withSwiftMessage(function ($message) use ($trackingData) {
                $message->getHeaders()->addTextHeader('X-Template-Name', $trackingData['template_name']);
            });
        }
    }

    /**
     * Track the sent email.
     *
     * @param  string|array  $to
     *
     * @throws \Inbounder\Exceptions\MailgunTrackingException
     */
    private function trackEmail(string $messageId, $to, Mailable $mailable, array $trackingData): void
    {
        try {
            $recipient = is_array($to) ? $to[0] : $to;

            $emailData = [
                'from_address' => $trackingData['from_address'] ?? config('mailgun.outbound.default_from.address'),
                'from_name' => $trackingData['from_name'] ?? config('mailgun.outbound.default_from.name'),
                'subject' => $mailable->subject ?? $trackingData['subject'] ?? null,
                'template_name' => $trackingData['template_name'] ?? null,
                'campaign_id' => $trackingData['campaign_id'] ?? null,
                'user_id' => $trackingData['user_id'] ?? null,
                'metadata' => $trackingData['metadata'] ?? null,
            ];

            $this->trackingService->createOutboundEmail($messageId, $recipient, $emailData);

            Log::info('Outbound email tracked', [
                'message_id' => $messageId,
                'recipient' => $recipient,
                'campaign_id' => $trackingData['campaign_id'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track outbound email', [
                'message_id' => $messageId,
                'recipient' => $to,
                'error' => $e->getMessage(),
            ]);
            throw new MailgunTrackingException('Failed to track outbound email: '.$e->getMessage());
        }
    }

    /**
     * Get tracking statistics for a campaign.
     */
    public function getCampaignStats(string $campaignId): array
    {
        return $this->trackingService->getCampaignStats($campaignId);
    }

    /**
     * Get tracking statistics for a user.
     */
    public function getUserStats(string $userId): array
    {
        return $this->trackingService->getUserStats($userId);
    }

    /**
     * Get outbound email by message ID.
     */
    public function getEmailByMessageId(string $messageId): ?\Inbounder\Models\MailgunOutboundEmail
    {
        return $this->trackingService->getByMessageId($messageId);
    }
}
