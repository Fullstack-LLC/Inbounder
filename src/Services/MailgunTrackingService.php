<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inbounder\Exceptions\MailgunTrackingException;
use Inbounder\Models\MailgunOutboundEmail;

/**
 * Service for tracking outbound emails and linking them to webhook events.
 *
 * This service handles the creation of outbound email records and
 * updates them based on webhook events received from Mailgun.
 */
class MailgunTrackingService
{
    /**
     * Create a new outbound email record.
     *
     * @throws \Inbounder\Exceptions\MailgunTrackingException
     */
    public function createOutboundEmail(string $messageId, string $recipient, array $data = []): MailgunOutboundEmail
    {
        try {
            return MailgunOutboundEmail::create([
                'message_id' => $messageId,
                'recipient' => $recipient,
                'from_address' => $data['from_address'] ?? null,
                'from_name' => $data['from_name'] ?? null,
                'subject' => $data['subject'] ?? null,
                'template_name' => $data['template_name'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create outbound email record', [
                'message_id' => $messageId,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);
            throw new MailgunTrackingException('Failed to create outbound email record: '.$e->getMessage());
        }
    }

    /**
     * Get outbound email based on webhook event.
     *
     * @param string $messageId
     * @return MailgunOutboundEmail
     *
     * @throws ModelNotFoundException
     */
    public function getOutboundEmail(string $messageId): ?MailgunOutboundEmail
    {
        try {

            $outboundEmail = MailgunOutboundEmail::where('message_id', $messageId)->firstOrFail();

            return $outboundEmail;

        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
    }

    /**
     * Update email status based on event type.
     */
    private function updateEmailStatus(MailgunOutboundEmail $outboundEmail, string $eventType, array $eventData = []): void
    {
        $updateData = [];

        switch ($eventType) {
            case 'delivered':
                $updateData = [
                    'status' => 'delivered',
                    'delivered_at' => $eventData['timestamp'] ?? now(),
                ];
                break;

            case 'opened':
                $updateData = [
                    'status' => 'opened',
                    'opened_at' => $eventData['timestamp'] ?? now(),
                ];
                break;

            case 'clicked':
                $updateData = [
                    'status' => 'clicked',
                    'clicked_at' => $eventData['timestamp'] ?? now(),
                ];
                break;

            case 'bounced':
                $updateData = [
                    'status' => 'bounced',
                    'bounced_at' => $eventData['timestamp'] ?? now(),
                ];
                break;

            case 'complained':
                $updateData = [
                    'status' => 'complained',
                    'complained_at' => $eventData['timestamp'] ?? now(),
                ];
                break;

            case 'unsubscribed':
                $updateData = [
                    'status' => 'unsubscribed',
                    'unsubscribed_at' => $eventData['timestamp'] ?? now(),
                ];
                break;
        }

        if (! empty($updateData)) {
            $outboundEmail->update($updateData);
        }
    }

    /**
     * Get outbound email by message ID.
     */
    public function getByMessageId(string $messageId): ?MailgunOutboundEmail
    {
        return MailgunOutboundEmail::where('message_id', $messageId)->first();
    }

    /**
     * Get outbound emails by recipient.
     */
    public function getByRecipient(string $recipient, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return MailgunOutboundEmail::recipient($recipient)
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get outbound emails by campaign.
     */
    public function getByCampaign(string $campaignId, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return MailgunOutboundEmail::campaign($campaignId)
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get outbound emails by user.
     */
    public function getByUser(string $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return MailgunOutboundEmail::user($userId)
            ->orderBy('sent_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get email statistics for a campaign.
     */
    public function getCampaignStats(string $campaignId): array
    {
        $emails = MailgunOutboundEmail::campaign($campaignId)->get();

        return [
            'total_sent' => $emails->count(),
            'delivered' => $emails->where('status', 'delivered')->count(),
            'opened' => $emails->where('status', 'opened')->count(),
            'clicked' => $emails->where('status', 'clicked')->count(),
            'bounced' => $emails->where('status', 'bounced')->count(),
            'complained' => $emails->where('status', 'complained')->count(),
            'unsubscribed' => $emails->where('status', 'unsubscribed')->count(),
            'delivery_rate' => $emails->count() > 0 ? ($emails->where('status', 'delivered')->count() / $emails->count()) * 100 : 0,
            'open_rate' => $emails->where('status', 'delivered')->count() > 0 ? ($emails->where('status', 'opened')->count() / $emails->where('status', 'delivered')->count()) * 100 : 0,
            'click_rate' => $emails->where('status', 'opened')->count() > 0 ? ($emails->where('status', 'clicked')->count() / $emails->where('status', 'opened')->count()) * 100 : 0,
        ];
    }

    /**
     * Get email statistics for a user.
     */
    public function getUserStats(string $userId): array
    {
        $emails = MailgunOutboundEmail::user($userId)->get();

        return [
            'total_sent' => $emails->count(),
            'delivered' => $emails->where('status', 'delivered')->count(),
            'opened' => $emails->where('status', 'opened')->count(),
            'clicked' => $emails->where('status', 'clicked')->count(),
            'bounced' => $emails->where('status', 'bounced')->count(),
            'complained' => $emails->where('status', 'complained')->count(),
            'unsubscribed' => $emails->where('status', 'unsubscribed')->count(),
            'delivery_rate' => $emails->count() > 0 ? ($emails->where('status', 'delivered')->count() / $emails->count()) * 100 : 0,
            'open_rate' => $emails->where('status', 'delivered')->count() > 0 ? ($emails->where('status', 'opened')->count() / $emails->where('status', 'delivered')->count()) * 100 : 0,
            'click_rate' => $emails->where('status', 'opened')->count() > 0 ? ($emails->where('status', 'clicked')->count() / $emails->where('status', 'opened')->count()) * 100 : 0,
        ];
    }

    /**
     * Get cumulative email statistics for a campaign using the event log.
     */
    public function getCumulativeCampaignStats(string $campaignId): array
    {
        $emails = MailgunOutboundEmail::campaign($campaignId)->pluck('message_id');
        $events = \Inbounder\Models\MailgunEvent::whereIn('message_id', $emails)->get();

        return [
            'total_sent' => $emails->count(),
            'delivered' => $events->where('event', 'delivered')->unique('message_id')->count(),
            'opened' => $events->where('event', 'opened')->unique('message_id')->count(),
            'clicked' => $events->where('event', 'clicked')->unique('message_id')->count(),
            'bounced' => $events->where('event', 'bounced')->unique('message_id')->count(),
            'complained' => $events->where('event', 'complained')->unique('message_id')->count(),
            'unsubscribed' => $events->where('event', 'unsubscribed')->unique('message_id')->count(),
        ];
    }

    /**
     * Get cumulative email statistics for a user using the event log.
     */
    public function getCumulativeUserStats(string $userId): array
    {
        $emails = MailgunOutboundEmail::user($userId)->pluck('message_id');
        $events = \Inbounder\Models\MailgunEvent::whereIn('message_id', $emails)->get();

        return [
            'total_sent' => $emails->count(),
            'delivered' => $events->where('event', 'delivered')->unique('message_id')->count(),
            'opened' => $events->where('event', 'opened')->unique('message_id')->count(),
            'clicked' => $events->where('event', 'clicked')->unique('message_id')->count(),
            'bounced' => $events->where('event', 'bounced')->unique('message_id')->count(),
            'complained' => $events->where('event', 'complained')->unique('message_id')->count(),
            'unsubscribed' => $events->where('event', 'unsubscribed')->unique('message_id')->count(),
        ];
    }
}
