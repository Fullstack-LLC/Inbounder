<?php

namespace Inbounder\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Inbounder\Services\MailgunService;
use Inbounder\Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Mock the logging to avoid cluttering test output
    Log::shouldReceive('info')->andReturn(true);
    Log::shouldReceive('warning')->andReturn(true);
    Log::shouldReceive('error')->andReturn(true);
    config(['services.mailgun.webhook_signing_key' => 'test-signing-key']);

    // Set explicit model class configurations to fix null config issues
    config([
        'mailgun.database.webhooks.model' => \Inbounder\Models\MailgunEvent::class,
        'mailgun.database.inbound.model' => \Inbounder\Models\MailgunInboundEmail::class,
        'mailgun.database.outbound.model' => \Inbounder\Models\MailgunOutboundEmail::class,
        'mailgun.inbound.store_inbound.model' => \Inbounder\Models\MailgunInboundEmail::class,
    ]);
});

describe('Mailgun Webhook Events', function () {
    it('handles delivered event', function () {
        $payload = [
            'event-data' => [
                'event' => 'delivered',
                'timestamp' => time(),
                'recipient' => 'test@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-delivered-123',
                    ],
                ],
                'domain' => 'example.com',
                'ip' => '192.168.1.1',
                'geolocation' => [
                    'country' => 'US',
                    'region' => 'CA',
                    'city' => 'San Francisco',
                ],
                'client-info' => [
                    'user-agent' => 'Mozilla/5.0',
                    'device-type' => 'desktop',
                    'client-type' => 'browser',
                    'client-name' => 'Chrome',
                    'client-os' => 'Windows',
                ],
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'delivered')
            ->assertJsonPath('data.recipient', 'test@example.com')
            ->assertJsonPath('data.message_id', 'test-delivered-123');
    });

    it('handles bounced event', function () {
        $payload = [
            'event-data' => [
                'event' => 'bounced',
                'timestamp' => time(),
                'recipient' => 'bounce@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-bounce-123',
                    ],
                ],
                'reason' => 'Invalid email address',
                'code' => '550',
                'severity' => 'permanent',
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'bounced')
            ->assertJsonPath('data.reason', 'Invalid email address')
            ->assertJsonPath('data.code', '550');
    });

    it('handles complained event', function () {
        $payload = [
            'event-data' => [
                'event' => 'complained',
                'timestamp' => time(),
                'recipient' => 'complaint@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-complaint-123',
                    ],
                ],
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'complained');
    });

    it('handles unsubscribed event', function () {
        $payload = [
            'event-data' => [
                'event' => 'unsubscribed',
                'timestamp' => time(),
                'recipient' => 'unsub@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-unsub-123',
                    ],
                ],
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'unsubscribed');
    });

    it('handles opened event', function () {
        $payload = [
            'event-data' => [
                'event' => 'opened',
                'timestamp' => time(),
                'recipient' => 'open@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-open-123',
                    ],
                ],
                'client-info' => [
                    'user-agent' => 'Mozilla/5.0 (iPhone)',
                    'device-type' => 'mobile',
                    'client-type' => 'mobile',
                    'client-name' => 'Safari',
                    'client-os' => 'iOS',
                ],
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'opened')
            ->assertJsonPath('data.device_type', 'mobile');
    });

    it('handles clicked event', function () {
        $payload = [
            'event-data' => [
                'event' => 'clicked',
                'timestamp' => time(),
                'recipient' => 'click@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-click-123',
                    ],
                ],
                'client-info' => [
                    'user-agent' => 'Mozilla/5.0 (Macintosh)',
                    'device-type' => 'desktop',
                    'client-type' => 'browser',
                    'client-name' => 'Firefox',
                    'client-os' => 'macOS',
                ],
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'clicked')
            ->assertJsonPath('data.client_name', 'Firefox');
    });

    it('handles unknown event type', function () {
        $payload = [
            'event-data' => [
                'event' => 'unknown_event',
                'timestamp' => time(),
                'recipient' => 'test@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-unknown-123',
                    ],
                ],
            ],
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($payload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ])
            ->assertJsonPath('data.event', 'unknown_event');
    });

    it('only stores configured events in database', function () {
        // Configure to only store 'delivered' events
        config(['mailgun.database.webhooks.enabled' => true]);
        config(['mailgun.database.webhooks.store_events' => ['delivered']]);

        // Test delivered event (should be stored)
        $deliveredPayload = [
            'event-data' => [
                'event' => 'delivered',
                'timestamp' => time(),
                'recipient' => 'test@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-delivered-storage',
                    ],
                ],
            ],
        ];
        $deliveredRequest = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($deliveredPayload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $deliveredRequest->all());
        $response->assertStatus(200);

        // Verify delivered event was stored
        $this->assertDatabaseHas('mailgun_events', [
            'event_type' => 'delivered',
            'message_id' => 'test-delivered-storage',
        ]);

        // Test bounced event (should NOT be stored)
        $bouncedPayload = [
            'event-data' => [
                'event' => 'bounced',
                'timestamp' => time(),
                'recipient' => 'bounce@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-bounced-storage',
                    ],
                ],
            ],
        ];
        $bouncedRequest = \Inbounder\Tests\Helpers\MailgunTestHelper::createWebhookRequest($bouncedPayload, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $bouncedRequest->all());
        $response->assertStatus(200);

        // Verify bounced event was NOT stored
        $this->assertDatabaseMissing('mailgun_events', [
            'event_type' => 'bounced',
            'message_id' => 'test-bounced-storage',
        ]);
    });

    it('can create and store MailgunEvent model', function () {
        config(['mailgun.database.webhooks.enabled' => true]);

        $eventData = [
            'event_type' => 'delivered',
            'message_id' => 'test-model-123',
            'recipient' => 'test@example.com',
            'domain' => 'example.com',
            'ip' => '192.168.1.1',
            'country' => 'US',
            'region' => 'CA',
            'city' => 'San Francisco',
            'user_agent' => 'Mozilla/5.0',
            'device_type' => 'desktop',
            'client_type' => 'browser',
            'client_name' => 'Chrome',
            'client_os' => 'Windows',
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
            'event_timestamp' => now(),
            'raw_data' => ['test' => 'data'],
        ];

        $modelClass = config('mailgun.database.webhooks.model');
        $event = $modelClass::create($eventData);

        $this->assertDatabaseHas('mailgun_events', [
            'event_type' => 'delivered',
            'message_id' => 'test-model-123',
            'recipient' => 'test@example.com',
        ]);

        expect($event->event_type)->toBe('delivered');
        expect($event->raw_data)->toBe(['test' => 'data']);
    });
});

describe('Mailgun Inbound Email', function () {
    it('handles inbound email with all fields', function () {
        $payload = [
            'from' => 'sender@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Test Inbound Email',
            'body-plain' => 'This is a test email body in plain text',
            'body-html' => '<html><body><h1>Test Email</h1><p>This is a test email body in HTML</p></body></html>',
            'Message-Id' => 'test-inbound-123@example.com',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'recipient' => 'inbound@dchurch.us',
            'sender' => 'sender@example.com',
            'stripped-text' => 'This is the stripped text content',
            'stripped-html' => '<p>This is the stripped HTML content</p>',
            'stripped-signature' => '-- Test Signature',
            'message-headers' => json_encode([
                ['name' => 'From', 'value' => 'sender@example.com'],
                ['name' => 'To', 'value' => 'inbound@dchurch.us'],
                ['name' => 'Subject', 'value' => 'Test Inbound Email'],
            ]),
            'content-id-map' => json_encode([
                'attachment1' => 'cid:attachment1@example.com',
            ]),
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createInboundRequest($payload);
        $response = $this->postJson(route('mailgun.inbound'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Inbound email processed successfully',
            ])
            ->assertJsonPath('data.from', 'sender@example.com')
            ->assertJsonPath('data.to', 'inbound@dchurch.us')
            ->assertJsonPath('data.subject', 'Test Inbound Email')
            ->assertJsonPath('data.body_plain', 'This is a test email body in plain text')
            ->assertJsonPath('data.body_html', '<html><body><h1>Test Email</h1><p>This is a test email body in HTML</p></body></html>');
    });

    it('handles inbound email with attachments', function () {
        $payload = [
            'from' => 'sender@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Test Email with Attachments',
            'body-plain' => 'This email has attachments',
            'body-html' => '<p>This email has attachments</p>',
            'Message-Id' => 'test-attachments-123@example.com',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'recipient' => 'inbound@dchurch.us',
            'sender' => 'sender@example.com',
            'attachment-count' => '2',
        ];
        $request = \Inbounder\Tests\Helpers\MailgunTestHelper::createInboundRequest($payload);
        $response = $this->postJson(route('mailgun.inbound'), $request->all());

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Inbound email processed successfully',
            ])
            ->assertJsonPath('data.subject', 'Test Email with Attachments');
    });

    it('can create and store MailgunInboundEmail model', function () {
        config(['mailgun.database.inbound.enabled' => true]);

        $emailData = [
            'from' => 'sender@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Test Inbound Email',
            'body_plain' => 'This is a test email body',
            'body_html' => '<p>This is a test email body</p>',
            'message_id' => 'test-inbound-model@example.com',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'recipient' => 'inbound@dchurch.us',
            'sender' => 'sender@example.com',
            'stripped_text' => 'Test stripped text',
            'stripped_html' => '<p>Test stripped html</p>',
            'stripped_signature' => 'Test signature',
            'message_headers' => 'Test headers',
            'content_id_map' => 'Test content map',
            'raw_data' => ['test' => 'inbound data'],
        ];

        $modelClass = config('mailgun.database.inbound.model');
        $email = $modelClass::create($emailData);

        $this->assertDatabaseHas('mailgun_inbound_emails', [
            'from' => 'sender@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Test Inbound Email',
        ]);

        expect($email->from)->toBe('sender@example.com');
        expect($email->raw_data)->toBe(['test' => 'inbound data']);
    });
});

describe('Mailgun Service Direct Testing', function () {
    it('can process webhook data through service directly', function () {
        $service = app(MailgunService::class);

        $request = request()->merge([
            'event-data' => [
                'event' => 'delivered',
                'timestamp' => time(),
                'recipient' => 'test@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'direct-test-123',
                    ],
                ],
            ],
        ]);

        $result = $service->handleWebhook($request);

        expect($result)->toHaveKey('status', 'success');
        expect($result)->toHaveKey('data.event', 'delivered');
        expect($result)->toHaveKey('data.recipient', 'test@example.com');
    });

    it('can process inbound email through service directly', function () {
        $service = app(MailgunService::class);

        $request = request()->merge([
            'from' => 'direct@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Direct Service Test',
            'body-plain' => 'Testing service directly',
        ]);

        $result = $service->handleInbound($request);

        expect($result)->toHaveKey('status', 'success');
        expect($result)->toHaveKey('data.from', 'direct@example.com');
        expect($result)->toHaveKey('data.subject', 'Direct Service Test');
    });
});

describe('Mailgun Service Provider', function () {
    it('can publish service provider assets', function () {
        // Test that the config is merged
        expect(config('mailgun.database.webhooks.enabled'))->toBe(false);
        expect(config('mailgun.database.inbound.enabled'))->toBe(false);

        // Test that the middleware is registered
        expect(app('router')->getMiddleware()['verify.mailgun.webhook'])->toBe(\Inbounder\Http\Middleware\VerifyMailgunWebhook::class);

        // Test that the service is registered
        expect(app(MailgunService::class))->toBeInstanceOf(MailgunService::class);
    });
});

describe('Mailgun Service Error Handling', function () {
    it('throws exception when webhook processing fails due to invalid model', function () {
        // Configure invalid model to cause an error
        config(['mailgun.database.webhooks.enabled' => true]);
        config(['mailgun.database.webhooks.model' => 'InvalidModelClass']);
        config(['mailgun.database.webhooks.store_events' => ['delivered']]);

        $payload = [
            'event-data' => [
                'event' => 'delivered',
                'timestamp' => time(),
                'recipient' => 'test@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-exception-123',
                    ],
                ],
                'domain' => 'example.com',
            ],
        ];

        $service = app(MailgunService::class);

        // This should throw an exception due to invalid model class
        $this->expectException(\Inbounder\Exceptions\MailgunWebhookException::class);
        $service->handleWebhook(request()->merge($payload));
    });

    it('throws exception when inbound processing fails due to invalid model', function () {
        // Configure invalid model to cause an error
        config(['mailgun.database.inbound.enabled' => true]);
        config(['mailgun.database.inbound.model' => 'InvalidModelClass']);

        $payload = [
            'from' => 'sender@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Test Email',
        ];

        $service = app(MailgunService::class);

        // This should throw an exception due to invalid model class
        $this->expectException(\Inbounder\Exceptions\MailgunInboundException::class);
        $service->handleInbound(request()->merge($payload));
    });
});
