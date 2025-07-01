<?php

namespace Inbounder\Tests\Feature;

use Inbounder\Tests\TestCase;

uses(TestCase::class);

describe('Mailgun Webhook Signature Verification', function () {
    beforeEach(function () {
        config(['mailgun.webhook.verify_signature' => true]);
        config(['mailgun.force_signature_testing' => true]);
        config(['services.mailgun.webhook_signing_key' => 'test-signing-key']);
    });

    it('rejects webhook with invalid signature', function () {
        $payload = [
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'invalid-signature',
            'event-data' => [
                'event' => 'delivered',
                'recipient' => 'test@example.com',
                'message' => [
                    'headers' => [
                        'message-id' => 'test-invalid-signature',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('mailgun.webhook'), $payload);
        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid webhook signature']);
    });

    it('rejects inbound with invalid signature', function () {
        $payload = [
            'from' => 'sender@example.com',
            'to' => 'inbound@dchurch.us',
            'subject' => 'Test Inbound Email',
            'body-plain' => 'This is a test email body',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'invalid-signature',
        ];

        $response = $this->postJson('/api/mail/inbound', $payload);
        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid webhook signature']);
    });
});
