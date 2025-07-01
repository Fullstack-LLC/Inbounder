<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inbounder\Events\InboundEmailReceived;
use Inbounder\Tests\Helpers\MailgunTestHelper;
use Inbounder\Tests\TestCase;

/**
 * Test inbound email event dispatching.
 */
class InboundEmailEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mailgun.webhook_signing_key' => 'test-signing-key']);
    }

    /**
     * Test that InboundEmailReceived event is dispatched when inbound email is processed.
     */
    public function test_inbound_email_event_is_dispatched(): void
    {
        Event::fake();

        $payload = [
            'from' => 'sender@example.com',
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body-plain' => 'Test body',
            'body-html' => '<p>Test body</p>',
            'Message-Id' => '<test-message-id@example.com>',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'recipient' => 'recipient@example.com',
            'sender' => 'sender@example.com',
        ];

        $request = MailgunTestHelper::createInboundRequest($payload);

        $response = $this->post(route('mailgun.inbound'), $payload, $request->headers->all());

        $response->assertStatus(200);

        Event::assertDispatched(InboundEmailReceived::class, function ($event) use ($payload) {
            return $event->emailData['from'] === $payload['from']
                && $event->emailData['to'] === $payload['to']
                && $event->emailData['subject'] === $payload['subject']
                && $event->emailData['body_plain'] === $payload['body-plain'];
        });
    }

    /**
     * Test that event name is configurable.
     */
    public function test_event_name_is_configurable(): void
    {
        $customEventName = 'custom.inbound.email.event';
        config(['mailgun.events.inbound_email_received' => $customEventName]);

        $this->assertEquals($customEventName, InboundEmailReceived::getEventName());
    }

    /**
     * Test that event contains all email data.
     */
    public function test_event_contains_complete_email_data(): void
    {
        Event::fake();

        $payload = [
            'from' => 'sender@example.com',
            'to' => 'recipient@example.com',
            'subject' => 'Test Subject',
            'body-plain' => 'Test body',
            'body-html' => '<p>Test body</p>',
            'Message-Id' => '<test-message-id@example.com>',
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'recipient' => 'recipient@example.com',
            'sender' => 'sender@example.com',
            'stripped-text' => 'Stripped text',
            'stripped-html' => '<p>Stripped HTML</p>',
            'stripped-signature' => 'Stripped signature',
            'message-headers' => 'Message headers',
            'content-id-map' => 'Content ID map',
        ];

        $request = MailgunTestHelper::createInboundRequest($payload);

        $this->post(route('mailgun.inbound'), $payload, $request->headers->all());

        Event::assertDispatched(InboundEmailReceived::class, function ($event) use ($payload) {
            $emailData = $event->emailData;

            return $emailData['from'] === $payload['from']
                && $emailData['to'] === $payload['to']
                && $emailData['subject'] === $payload['subject']
                && $emailData['body_plain'] === $payload['body-plain']
                && $emailData['body_html'] === $payload['body-html']
                && $emailData['message_id'] === $payload['Message-Id']
                && $emailData['timestamp'] === $payload['timestamp']
                && $emailData['token'] === $payload['token']
                && $emailData['signature'] === $payload['signature']
                && $emailData['recipient'] === $payload['recipient']
                && $emailData['sender'] === $payload['sender']
                && $emailData['stripped_text'] === $payload['stripped-text']
                && $emailData['stripped_html'] === $payload['stripped-html']
                && $emailData['stripped_signature'] === $payload['stripped-signature']
                && $emailData['message_headers'] === $payload['message-headers']
                && $emailData['content_id_map'] === $payload['content-id-map'];
        });
    }
}
