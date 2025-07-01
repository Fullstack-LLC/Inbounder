<?php

declare(strict_types=1);

namespace Inbounder\Tests\Helpers;

use Illuminate\Http\Request;

/**
 * Helper class for Mailgun testing.
 *
 * This class provides utility methods for creating test requests and data
 * that simulate Mailgun webhooks and inbound emails.
 */
class MailgunTestHelper
{
    /**
     * Create a test request with valid Mailgun webhook signature.
     *
     * @param  array  $data  The webhook data to include in the request.
     * @param  string  $signingKey  The signing key to use for signature generation.
     * @return Request The test request with valid signature.
     */
    public static function createWebhookRequest(array $data, string $signingKey): Request
    {
        $timestamp = time();
        $token = 'test-token-'.uniqid();
        $signature = hash_hmac('sha256', $timestamp.$token, $signingKey);

        $requestData = array_merge($data, [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $signature,
        ]);

        return Request::create(url(route('mailgun.webhook')), 'POST', $requestData);
    }

    /**
     * Create a test request with invalid Mailgun webhook signature.
     *
     * @param  array  $data  The webhook data to include in the request.
     * @return Request The test request with invalid signature.
     */
    public static function createInvalidWebhookRequest(array $data): Request
    {
        $requestData = array_merge($data, [
            'timestamp' => time(),
            'token' => 'invalid-token',
            'signature' => 'invalid-signature',
        ]);

        return Request::create(url(route('mailgun.webhook')), 'POST', $requestData);
    }

    /**
     * Create a test request for inbound email.
     *
     * @param  array  $data  The inbound email data to include in the request.
     * @return Request The test request for inbound email.
     */
    public static function createInboundRequest(array $data): Request
    {
        return Request::create('/api/mail/inbound', 'POST', $data);
    }

    /**
     * Get sample webhook data for delivered event.
     *
     * @return array Sample delivered webhook data.
     */
    public static function getDeliveredWebhookData(): array
    {
        return [
            'event-data' => [
                'event' => 'delivered',
                'timestamp' => time(),
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id-'.uniqid(),
                    ],
                ],
                'recipient' => 'test@example.com',
                'domain' => 'example.com',
                'ip' => '192.168.1.1',
                'geolocation' => [
                    'country' => 'US',
                    'region' => 'CA',
                    'city' => 'San Francisco',
                ],
                'client-info' => [
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'device-type' => 'desktop',
                    'client-type' => 'browser',
                    'client-name' => 'Chrome',
                    'client-os' => 'Windows',
                ],
            ],
        ];
    }

    /**
     * Get sample webhook data for bounced event.
     *
     * @return array Sample bounced webhook data.
     */
    public static function getBouncedWebhookData(): array
    {
        return [
            'event-data' => [
                'event' => 'bounced',
                'timestamp' => time(),
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id-'.uniqid(),
                    ],
                ],
                'recipient' => 'bounce@example.com',
                'domain' => 'example.com',
                'reason' => 'Invalid recipient',
                'code' => '550',
                'error' => 'User not found',
                'severity' => 'permanent',
            ],
        ];
    }

    /**
     * Get sample webhook data for complained event.
     *
     * @return array Sample complained webhook data.
     */
    public static function getComplainedWebhookData(): array
    {
        return [
            'event-data' => [
                'event' => 'complained',
                'timestamp' => time(),
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id-'.uniqid(),
                    ],
                ],
                'recipient' => 'spam@example.com',
                'domain' => 'example.com',
            ],
        ];
    }

    /**
     * Get sample webhook data for unsubscribed event.
     *
     * @return array Sample unsubscribed webhook data.
     */
    public static function getUnsubscribedWebhookData(): array
    {
        return [
            'event-data' => [
                'event' => 'unsubscribed',
                'timestamp' => time(),
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id-'.uniqid(),
                    ],
                ],
                'recipient' => 'unsub@example.com',
                'domain' => 'example.com',
            ],
        ];
    }

    /**
     * Get sample webhook data for opened event.
     *
     * @return array Sample opened webhook data.
     */
    public static function getOpenedWebhookData(): array
    {
        return [
            'event-data' => [
                'event' => 'opened',
                'timestamp' => time(),
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id-'.uniqid(),
                    ],
                ],
                'recipient' => 'user@example.com',
                'domain' => 'example.com',
                'ip' => '192.168.1.1',
                'geolocation' => [
                    'country' => 'US',
                    'region' => 'CA',
                    'city' => 'San Francisco',
                ],
                'client-info' => [
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'device-type' => 'desktop',
                    'client-type' => 'browser',
                    'client-name' => 'Chrome',
                    'client-os' => 'Windows',
                ],
            ],
        ];
    }

    /**
     * Get sample webhook data for clicked event.
     *
     * @return array Sample clicked webhook data.
     */
    public static function getClickedWebhookData(): array
    {
        return [
            'event-data' => [
                'event' => 'clicked',
                'timestamp' => time(),
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id-'.uniqid(),
                    ],
                ],
                'recipient' => 'user@example.com',
                'domain' => 'example.com',
                'ip' => '192.168.1.1',
                'geolocation' => [
                    'country' => 'US',
                    'region' => 'CA',
                    'city' => 'San Francisco',
                ],
                'client-info' => [
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'device-type' => 'desktop',
                    'client-type' => 'browser',
                    'client-name' => 'Chrome',
                    'client-os' => 'Windows',
                ],
            ],
        ];
    }

    /**
     * Get sample inbound email data.
     *
     * @return array Sample inbound email data.
     */
    public static function getInboundEmailData(): array
    {
        return [
            'from' => 'sender@example.com',
            'to' => 'recipient@example.com',
            'subject' => 'Test Email Subject',
            'body-plain' => 'This is a test email body in plain text.',
            'body-html' => '<html><body><h1>Test Email</h1><p>This is a test email body in HTML.</p></body></html>',
            'Message-Id' => 'test-message-id-'.uniqid(),
            'timestamp' => time(),
            'token' => 'test-token-'.uniqid(),
            'signature' => 'test-signature-'.uniqid(),
            'recipient' => 'recipient@example.com',
            'sender' => 'sender@example.com',
            'stripped-text' => 'This is the stripped text content.',
            'stripped-html' => '<p>This is the stripped HTML content.</p>',
            'stripped-signature' => '-- Test Signature',
            'message-headers' => json_encode([
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'recipient@example.com'],
                ['name' => 'Subject', 'value' => 'Test Email Subject'],
            ]),
            'content-id-map' => json_encode([
                'attachment1' => 'cid:attachment1@example.com',
            ]),
        ];
    }
}
