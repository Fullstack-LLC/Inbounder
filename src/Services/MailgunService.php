<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inbounder\Events\InboundEmailReceived;
use Inbounder\Events\WebhookEventReceived;

use Inbounder\Exceptions\MailgunInboundException;
use Inbounder\Exceptions\MailgunTrackingException;
use Inbounder\Exceptions\MailgunWebhookException;
use Inbounder\Exceptions\NotAuthorizedToSendException;
use Inbounder\Models\MailgunOutboundEmail;
use Inbounder\Tests\Feature\MailgunTrackingTest;

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
            $emailData = $this->parseInboundEmail($request);

            /** Make sure the sender is authorized to send emails to this system. */
            $from = strtolower($emailData['sender']);

            if (! $this->authorizedToSend($from)) {
                throw new NotAuthorizedToSendException($from);
            }

            $this->processInboundEmail($emailData);

            logger()->info($emailData['sender'] . ' has successfully created a new inbound email.');

            return [
                'status' => 'success',
                'message' => 'Inbound email processed successfully',
            ];
        } catch (NotAuthorizedToSendException $e) {

            logger()->notice($e->getMessage());

            return [
                'status' => 'unauthorized',
                'message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {

            logger()->error($e->getMessage());

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
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
        $userId = $this->getUserId($request->input('sender'));

        if ($userId === 0) {
            throw new NotAuthorizedToSendException(strtolower($request->input('sender')));
        }

        return [
            'from' => strtolower($request->input('from')),
            'to' => strtolower($request->input('To')) ?? strtolower($request->input('to')) ?? strtolower($request->input('recipient')),
            'subject' => $request->input('subject'),
            'body_plain' => $request->input('body-plain'),
            'body_html' => $request->input('body-html'),
            'message_id' => $request->input('Message-Id'),
            'timestamp' => (int) $request->input('timestamp'),
            'token' => $request->input('token'),
            'signature' => $request->input('signature'),
            'attachments' => $request->file('attachment') ?? [],
            'recipient' => strtolower($request->input('recipient')),
            'user_id' => $userId,
            'sender' => strtolower($request->input('sender')),
            'stripped_text' => $request->input('stripped-text'),
            'stripped_html' => $request->input('stripped-html'),
            'stripped_signature' => $request->input('stripped-signature'),
            'message_headers' => $request->input('message-headers'),
            'content_id_map' => $request->input('content-id-map'),
            'raw_data' => $request->all(),
        ];
    }

    /**
     * Process the inbound email
     *
     * @param  array  $emailData  The parsed inbound email data.
     */
    private function processInboundEmail(array $emailData): void
    {
        $inboundEnabled = $this->getConfig('mailgun.database.inbound.enabled', false);

        if (! $inboundEnabled) {
            logger()->debug('Inbound emails are disabled.');
            return;
        }

        $modelClass = $this->getConfig('mailgun.database.inbound.model');

        $modelClass::create($emailData);

        event(new InboundEmailReceived($emailData));
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

            $webhookData = $this->parseWebhookData($request);

            $this->processWebhook($webhookData);

            return [
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ];
        } catch (MailgunTrackingException $e) {
            logger()->notice($e->getMessage());

            return [
                'status' => 'notice',
                'message' => $e->getMessage(),
            ];

        } catch (\Throwable $e) {
            throw new MailgunWebhookException('Failed to process webhook: '.$e->getMessage(), 0, $e);
        }
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

        $jsonFields = [
            'delivery_status', 'envelope', 'flags', 'tags', 'campaigns', 'user_variables'
        ];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                if (is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        continue;
                    }
                }

                if (is_array($data[$field]) || !is_string($data[$field])) {
                    $data[$field] = json_encode($data[$field]);
                }
            }
        }

        return $data;
    }

    /**
     * Process the webhook (add your business logic here).
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function processWebhook(array $webhookData): void
    {
        $outboundMessage = $this->getInternalMessageId($webhookData);

        if (! $outboundMessage) {
            //logger()->notice('Unable to match message: ' . $webhookData['message_id'] . ' to an outbound email.');
            return;
        }

        try {

            if ($this->getConfig('mailgun.database.webhooks.enabled', false)) {
                $this->storeWebhookEvent($webhookData);
                $this->updateOutboundEmailStatus($outboundMessage, $webhookData['event'], $webhookData);
            }

            $this->dispatchWebhookEvent($webhookData);

        } catch (ModelNotFoundException $e) {
            logger()->notice('Unable to match message: ' . $webhookData['message_id'] . ' to an outbound email.');
        } catch (\Exception $e) {
            throw new MailgunWebhookException('Failed to update outbound email tracking', 0, $e);
        }

    }

    /**
     * Handle delivered event.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function updateOutboundEmailStatus(MailgunOutboundEmail $outboundEmail, string $eventType, array $eventData = []): void
    {
        // Get the configured outbound events from the config.
        $outboundEvents = $this->getConfig('mailgun.webhook_events.trigger_events', []);

        if (! in_array($eventType, $outboundEvents)) {
            return;
        }

        $outboundEmail->update([
            $eventType . '_at' => now(),
        ]);
    }

    /**
     * Dispatch webhook event if enabled and configured for this event type.
     *
     * @param  array  $webhookData  The parsed webhook data.
     */
    private function dispatchWebhookEvent(array $webhookData): void
    {
        $webhookEventsConfig = $this->getConfig('mailgun.webhook_events', []);

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
        } catch (\Exception $e) {
            logger()->error($e->getMessage());
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
        $storeEvents = $this->getConfig('mailgun.database.webhooks.store_events', []);
        if (! in_array($webhookData['event'], $storeEvents)) {
            return;
        }

        try {
            $modelClass = $this->getConfig('mailgun.database.webhooks.model');
            $modelClass::create($webhookData);
        } catch (\Exception $e) {
            logger()->error('Failed to store webhook event: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if the sender is authorized to send emails to this system.
     *
     * @param  string  $from  The sender's email address.
     * @return bool True if authorized, false otherwise.
     */
    private function authorizedToSend(string $from): bool
    {
        $authorizationEnabled = $this->getConfig('mailgun.authorization.enabled', false);

        if (! $authorizationEnabled) {
            return true;
        }

        $userModel = $this->getConfig('mailgun.user_model', \App\Models\User::class);

        if (! class_exists($userModel)) {
            return false;
        }

        $user = $userModel::where($this->getConfig('mailgun.authorization.user_field'), $from)->first();

        if (!$user) {
            return false;
        }

        $method = $this->getConfig('mailgun.authorization.method');

        switch ($method) {
            case 'none':
                return true;
            case 'spatie':
                $spatiePermission = $this->getConfig('mailgun.authorization.spatie_permission');

                return method_exists($user, 'hasPermissionTo')
                    ? $user->hasPermissionTo($spatiePermission)
                    : false;
            case 'policy':
                $policyMethod = $this->getConfig('mailgun.authorization.policy_method');

                return method_exists($user, 'can')
                    ? $user->can($policyMethod)
                    : false;
            case 'gate':
                $gateName = $this->getConfig('mailgun.authorization.gate_name');
                return Gate::allows($gateName, $user);
            default:
                $gateName = $this->getConfig('mailgun.authorization.gate_name');
                return Gate::allows($gateName, $user);
        }
    }

    /**
     * Get the user ID from the sender's email address.
     *
     * @param  string  $from  The sender's email address.
     * @return int The user ID.
     */
    private function getUserId(string $from): int
    {
        $userModel = $this->getConfig('mailgun.user_model', \App\Models\User::class);

        if (!class_exists($userModel)) {
            return 0;
        }

        $user = $userModel::where('email', $from)->first();

        if (!$user) {
            return 0;
        }

        return $user->id;
    }

    /**
     * Get the internal message ID from the user variables.
     *
     * @param  array  $webhookData  The user variables.
     * @return MailgunOutboundEmail|null The outbound email record.
     */
    private function getInternalMessageId(array $webhookData): ?MailgunOutboundEmail
    {
        $json = $webhookData['user_variables'] ?? null;

        if (! $json) {
            return null;
        }

        $userVariables = json_decode($json, true);

        if (! is_array($userVariables) || empty($userVariables['outbound_message_id'])) {
            return null;
        }

        return MailgunOutboundEmail::where('message_id', $userVariables['outbound_message_id'])->first();
    }
}
