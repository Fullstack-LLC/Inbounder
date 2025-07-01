<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inbounder\Exceptions\MailgunInboundException;
use Inbounder\Exceptions\MailgunWebhookException;
use Inbounder\Services\MailgunService;
use Inbounder\Services\MailgunTrackingService;
use Inbounder\Tests\TestCase;

/**
 * Test error handling scenarios in MailgunService.
 */
class MailgunServiceErrorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mailgun.webhook_signing_key' => 'test-signing-key']);
    }

    /**
     * Test that storeWebhookEvent throws exception when database operation fails.
     */
    public function test_store_webhook_event_throws_exception_when_database_fails(): void
    {
        config([
            'mailgun.database.webhooks.enabled' => true,
            'mailgun.database.webhooks.store_events' => ['delivered'],
            'mailgun.database.webhooks.model' => 'NonExistentModel',
        ]);

        $webhookData = [
            'event' => 'delivered',
            'message_id' => 'test-message-id',
            'recipient' => 'test@example.com',
            'domain' => 'example.com',
            'timestamp' => time(),
        ];

        $service = new MailgunService(new MailgunTrackingService);

        $this->expectException(\Error::class);

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('storeWebhookEvent');
        $method->setAccessible(true);
        $method->invoke($service, $webhookData);
    }

    /**
     * Test that storeWebhookEvent skips events not in store_events config.
     */
    public function test_store_webhook_event_skips_unconfigured_events(): void
    {
        config([
            'mailgun.database.webhooks.enabled' => true,
            'mailgun.database.webhooks.store_events' => ['delivered'], // Only delivered events
            'mailgun.database.webhooks.model' => \Inbounder\Models\MailgunEvent::class,
        ]);

        $webhookData = [
            'event' => 'bounced', // Event not in store_events
            'message_id' => 'test-message-id',
            'recipient' => 'test@example.com',
        ];

        $service = new MailgunService(new MailgunTrackingService);

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('storeWebhookEvent');
        $method->setAccessible(true);

        // Should not throw an exception and should return early
        $result = $method->invoke($service, $webhookData);

        $this->assertNull($result);
    }

    /**
     * Test that storeWebhookEvent handles null timestamp correctly.
     */
    public function test_store_webhook_event_handles_null_timestamp(): void
    {
        config([
            'mailgun.database.webhooks.enabled' => true,
            'mailgun.database.webhooks.store_events' => ['delivered'],
            'mailgun.database.webhooks.model' => \Inbounder\Models\MailgunEvent::class,
        ]);

        $webhookData = [
            'event' => 'delivered',
            'message_id' => 'test-message-id',
            'recipient' => 'test@example.com',
            'domain' => 'example.com',
            'ip' => null,
            'country' => null,
            'region' => null,
            'city' => null,
            'user_agent' => null,
            'device_type' => null,
            'client_type' => null,
            'client_name' => null,
            'client_os' => null,
            'reason' => null,
            'code' => null,
            'error' => null,
            'severity' => null,
            'delivery_status' => null,
            'envelope' => null,
            'flags' => null,
            'tags' => null,
            'campaigns' => null,
            'user_variables' => null,
            'timestamp' => null, // Null timestamp
        ];

        $service = new MailgunService(new MailgunTrackingService);

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('storeWebhookEvent');
        $method->setAccessible(true);

        // Should not throw an exception
        $result = $method->invoke($service, $webhookData);

        $this->assertNull($result);
    }

    /**
     * Test that handleInbound throws exception when processing fails.
     */
    public function test_handle_inbound_throws_exception_when_processing_fails(): void
    {
        $service = new MailgunService(new MailgunTrackingService);

        // Create a request that will cause an exception in processInboundEmail
        // by enabling database storage with an invalid model
        config([
            'mailgun.database.inbound.enabled' => true,
            'mailgun.database.inbound.model' => 'NonExistentModel',
        ]);

        $payload = [
            'from' => 'sender@example.com',
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body-plain' => 'Test body',
            'Message-Id' => '<test-message-id@example.com>',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'recipient' => 'recipient@example.com',
            'sender' => 'sender@example.com',
        ];

        $request = Request::create('/test', 'POST', $payload);

        $this->expectException(MailgunInboundException::class);

        $service->handleInbound($request);
    }

    /**
     * Test that handleWebhook throws exception when processing fails.
     */
    public function test_handle_webhook_throws_exception_when_processing_fails(): void
    {
        $service = new MailgunService(new MailgunTrackingService);

        // Create a request that will cause an exception in processWebhook
        // by enabling database storage with an invalid model
        config([
            'mailgun.database.webhooks.enabled' => true,
            'mailgun.database.webhooks.store_events' => ['delivered'],
            'mailgun.database.webhooks.model' => 'NonExistentModel',
        ]);

        $payload = [
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
            ],
        ];

        $request = Request::create('/test', 'POST', $payload);

        $this->expectException(MailgunWebhookException::class);

        $service->handleWebhook($request);
    }
}
