<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inbounder\Events\InboundEmailReceived;
use Inbounder\Events\WebhookEventReceived;
use Inbounder\Exceptions\MailgunInboundException;
use Inbounder\Exceptions\MailgunWebhookException;

class MailgunService
{
    /**
     * The tracking service instance.
     */
    private MailgunTrackingService $trackingService;

    /**
     * Create a new MailgunService instance.
     */
    public function __construct(MailgunTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Handle inbound emails from Mailgun.
     *
     * @param  Request  $request  The inbound email HTTP request from Mailgun.
     * @return array The result of processing the inbound email.
     *
     * @throws MailgunInboundException If processing fails.
     */
    public function handleInbound(Request $request): array
    {
        try {
            Log::info('Mailgun inbound webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
            ]);

            $emailData = $this->parseInboundEmail($request);
            $this->processInboundEmail($emailData);

            return [
                'status' => 'success',
                'message' => 'Inbound email processed successfully',
                'data' => $emailData,
            ];
        } catch (\Throwable $e) {
            Log::error('Error processing Mailgun inbound webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new MailgunInboundException('Failed to process inbound email: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Handle webhooks from Mailgun (delivery, bounces, etc.).
     *
     * @param  Request  $request  The webhook HTTP request from Mailgun.
     * @return array The result of processing the webhook.
     *
     * @throws MailgunWebhookException If processing fails.
     */
    public function handleWebhook(Request $request): array
    {
        try {
            Log::info('Mailgun webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
            ]);

            $webhookData = $this->parseWebhookData($request);
            $this->processWebhook($webhookData);

            return [
                'status' => 'success',
                'message' => 'Webhook processed successfully',
                'data' => $webhookData,
            ];
        } catch (\Throwable $e) {
            Log::error('Error processing Mailgun webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new MailgunWebhookException('Failed to process webhook: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Parse inbound email data from the request.
     *
     * @param  Request  $request  The inbound email HTTP request.
     * @return array Parsed inbound email data.
     */
    private function parseInboundEmail(Request $request): array
    {
        return [
            'from' => $request->input('from'),
            'to' => $request->input('to'),
            'subject' => $request->input('subject'),
            'body_plain' => $request->input('body-plain'),
            'body_html' => $request->input('body-html'),
            'message_id' => $request->input('Message-Id'),
            'timestamp' => (int) $request->input('timestamp'),
            'token' => $request->input('token'),
            'signature' => $request->input('signature'),
            'attachments' => $request->file('attachment') ?? [],
            'recipient' => $request->input('recipient'),
            'sender' => $request->input('sender'),
            'stripped_text' => $request->input('stripped-text'),
            'stripped_html' => $request->input('stripped-html'),
            'stripped_signature' => $request->input('stripped-signature'),
            'message_headers' => $request->input('message-headers'),
            'content_id_map' => $request->input('content-id-map'),
        ];
    }

    /**
     * Parse webhook data from the request.
     *
     * @param  Request  $request  The webhook HTTP request.
     * @return array Parsed webhook data.
     */
    private function parseWebhookData(Request $request): array
    {
        $data = [
            'event' => $request->input('event-data.event'),
            'timestamp' => $request->input('event-data.timestamp'),
            'message_id' => $request->input('event-data.message.headers.message-id'),
            'recipient' => $request->input('event-data.recipient'),
            'domain' => $request->input('event-data.domain'),
            'ip' => $request->input('event-data.ip'),
            'country' => $request->input('event-data.geolocation.country'),
            'region' => $request->input('event-data.geolocation.region'),
            'city' => $request->input('event-data.geolocation.city'),
            'user_agent' => $request->input('event-data.client-info.user-agent'),
            'device_type' => $request->input('event-data.client-info.device-type'),
            'client_type' => $request->input('event-data.client-info.client-type'),
            'client_name' => $request->input('event-data.client-info.client-name'),
            'client_os' => $request->input('event-data.client-info.client-os'),
            'reason' => $request->input('event-data.reason'),
            'code' => $request->input('event-data.code'),
            'error' => $request->input('event-data.error'),
            'severity' => $request->input('event-data.severity'),
            'delivery_status' => $request->input('event-data.delivery-status'),
            'envelope' => $request->input('event-data.envelope'),
            'flags' => $request->input('event-data.flags'),
            'tags' => $request->input('event-data.tags'),
            'campaigns' => $request->input('event-data.campaigns'),
            'user_variables' => $request->input('event-data.user-variables'),
        ];

        // Ensure string fields are actually strings
        $stringFields = [
            'event', 'message_id', 'recipient', 'domain', 'ip', 'country', 'region',
            'city', 'user_agent', 'device_type', 'client_type', 'client_name',
            'client_os', 'reason', 'code', 'error', 'severity'
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        // Ensure JSON fields are properly formatted
        $jsonFields = [
            'delivery_status', 'envelope', 'flags', 'tags', 'campaigns', 'user_variables'
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                // If it's already a string, try to decode it to ensure it's valid JSON
                if (is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // It's valid JSON, keep it as is
                        continue;
                    }
                }

                // If it's an array or invalid JSON string, encode it
                if (is_array($data[$field]) || !is_string($data[$field])) {
                    $data[$field] = json_encode($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * Process the inbound email (add your business logic here).
     *
     * @param  array  $emailData  The parsed inbound email data.
     */
    private function processInboundEmail(array $emailData): void
    {
        Log::info('Processing inbound email', [
            'from' => $emailData['from'],
            'to' => $emailData['to'],
            'subject' => $emailData['subject'],
            'message_id' => $emailData['message_id'],
        ]);

        if (config('mailgun.database.inbound.enabled', false)) {
            $modelClass = config('mailgun.database.inbound.model');
            $modelClass::create([
                'from' => $emailData['from'],
                'to' => $emailData['to'],
                'subject' => $emailData['subject'],
                'body_plain' => $emailData['body_plain'],
                'body_html' => $emailData['body_html'],
                'message_id' => $emailData['message_id'],
                'timestamp' => $emailData['timestamp'],
                'token' => $emailData['token'],
                'signature' => $emailData['signature'],
                'recipient' => $emailData['recipient'],
                'sender' => $emailData['sender'],
                'stripped_text' => $emailData['stripped_text'],
                'stripped_html' => $emailData['stripped_html'],
                'stripped_signature' => $emailData['stripped_signature'],
                'message_headers' => $emailData['message_headers'],
                'content_id_map' => $emailData['content_id_map'],
                'raw_data' => $emailData,
            ]);
        }

        // Dispatch the inbound email received event
        event(new InboundEmailReceived($emailData));
    }

    /**
     * Process the webhook (add your business logic here).
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function processWebhook(array $webhookData): void
    {
        Log::info('Processing webhook', [
            'event' => $webhookData['event'],
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);

        // Update outbound email tracking if this is a tracked email
        if ($webhookData['message_id']) {
            try {
                $this->trackingService->updateFromWebhook(
                    $webhookData['message_id'],
                    $webhookData['event'],
                    $webhookData
                );
            } catch (\Exception $e) {
                Log::warning('Failed to update outbound email tracking', [
                    'message_id' => $webhookData['message_id'],
                    'event' => $webhookData['event'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (config('mailgun.database.webhooks.enabled', false)) {
            $this->storeWebhookEvent($webhookData);
        }

        // Dispatch webhook event if enabled and configured for this event type
        $this->dispatchWebhookEvent($webhookData);

        switch ($webhookData['event']) {
            case 'accepted':
                $this->handleAccepted($webhookData);
                break;
            case 'delivered':
                $this->handleDelivered($webhookData);
                break;
            case 'rejected':
                $this->handleRejected($webhookData);
                break;
            case 'dropped':
                $this->handleDropped($webhookData);
                break;
            case 'bounced':
                $this->handleBounced($webhookData);
                break;
            case 'complained':
                $this->handleComplained($webhookData);
                break;
            case 'unsubscribed':
                $this->handleUnsubscribed($webhookData);
                break;
            case 'opened':
                $this->handleOpened($webhookData);
                break;
            case 'clicked':
                $this->handleClicked($webhookData);
                break;
            case 'stored':
                $this->handleStored($webhookData);
                break;
            default:
                Log::info('Unhandled webhook event', ['event' => $webhookData['event']]);
        }
    }

    /**
     * Handle delivered event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleDelivered(array $webhookData): void
    {
        Log::info('Email delivered', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Handle bounced event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleBounced(array $webhookData): void
    {
        Log::warning('Email bounced', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
            'reason' => $webhookData['reason'],
            'code' => $webhookData['code'],
        ]);
    }

    /**
     * Handle complained event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleComplained(array $webhookData): void
    {
        Log::warning('Email complained', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Handle unsubscribed event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleUnsubscribed(array $webhookData): void
    {
        Log::info('User unsubscribed', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Handle opened event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleOpened(array $webhookData): void
    {
        Log::info('Email opened', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
            'user_agent' => $webhookData['user_agent'],
        ]);
    }

    /**
     * Handle clicked event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleClicked(array $webhookData): void
    {
        Log::info('Email link clicked', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
            'user_agent' => $webhookData['user_agent'],
        ]);
    }

    /**
     * Handle accepted event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleAccepted(array $webhookData): void
    {
        Log::info('Email accepted', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Handle rejected event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleRejected(array $webhookData): void
    {
        Log::warning('Email rejected', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Handle dropped event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleDropped(array $webhookData): void
    {
        Log::warning('Email dropped', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Handle stored event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function handleStored(array $webhookData): void
    {
        Log::info('Email stored', [
            'message_id' => $webhookData['message_id'],
            'recipient' => $webhookData['recipient'],
        ]);
    }

    /**
     * Dispatch webhook event if enabled and configured for this event type.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function dispatchWebhookEvent(array $webhookData): void
    {
        $webhookEventsConfig = config('mailgun.webhook_events', []);

        if (! ($webhookEventsConfig['enabled'] ?? true)) {
            return;
        }

        $eventType = $webhookData['event'];
        $triggerEvents = $webhookEventsConfig['trigger_events'] ?? [];

        if (! ($triggerEvents[$eventType] ?? false)) {
            return;
        }

        try {
            event(new WebhookEventReceived($eventType, $webhookData, $webhookData));

            Log::info('Webhook event dispatched', [
                'event_type' => $eventType,
                'message_id' => $webhookData['message_id'] ?? null,
                'recipient' => $webhookData['recipient'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch webhook event', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store webhook event in database if enabled and configured.
     *
     * @param  array  $webhookData  The parsed webhook data.
     *
     * @throws \Exception If storing fails.
     */
    private function storeWebhookEvent(array $webhookData): void
    {
        $storeEvents = config('mailgun.database.webhooks.store_events', []);
        if (! in_array($webhookData['event'], $storeEvents)) {
            return;
        }

        try {
            $modelClass = config('mailgun.database.webhooks.model');
            $modelClass::create([
                'event_type' => $webhookData['event'] ?? null,
                'message_id' => $webhookData['message_id'] ?? null,
                'recipient' => $webhookData['recipient'] ?? null,
                'domain' => $webhookData['domain'] ?? null,
                'ip' => $webhookData['ip'] ?? null,
                'country' => $webhookData['country'] ?? null,
                'region' => $webhookData['region'] ?? null,
                'city' => $webhookData['city'] ?? null,
                'user_agent' => $webhookData['user_agent'] ?? null,
                'device_type' => $webhookData['device_type'] ?? null,
                'client_type' => $webhookData['client_type'] ?? null,
                'client_name' => $webhookData['client_name'] ?? null,
                'client_os' => $webhookData['client_os'] ?? null,
                'reason' => $webhookData['reason'] ?? null,
                'code' => $webhookData['code'] ?? null,
                'error' => $webhookData['error'] ?? null,
                'severity' => $webhookData['severity'] ?? null,
                'delivery_status' => $webhookData['delivery_status'] ?? null,
                'envelope' => $webhookData['envelope'] ?? null,
                'flags' => $webhookData['flags'] ?? null,
                'tags' => $webhookData['tags'] ?? null,
                'campaigns' => $webhookData['campaigns'] ?? null,
                'user_variables' => $webhookData['user_variables'] ?? null,
                'event_timestamp' => $webhookData['timestamp'] ? date('Y-m-d H:i:s', (int) $webhookData['timestamp']) : null,
                'raw_data' => $webhookData,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
