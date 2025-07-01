<?php

namespace Inbounder\Tests\Feature;

use Inbounder\Services\MailgunService;
use Inbounder\Tests\Helpers\MailgunTestHelper;
use Inbounder\Tests\TestCase;

uses(TestCase::class);

describe('Mailgun Test Helper', function () {
    beforeEach(function () {
        config(['services.mailgun.webhook_signing_key' => 'test-signing-key']);
    });

    it('can generate webhook data for all event types', function () {
        $events = ['delivered', 'bounced', 'complained', 'unsubscribed', 'opened', 'clicked'];

        foreach ($events as $event) {
            $method = 'get'.ucfirst($event).'WebhookData';
            $data = MailgunTestHelper::$method();

            expect($data)->toHaveKey('event-data.event', $event);
            expect($data)->toHaveKey('event-data.recipient');
            expect($data)->toHaveKey('event-data.message.headers.message-id');
        }
    });

    it('can generate bounce data with custom reasons', function () {
        $data = MailgunTestHelper::getBouncedWebhookData();

        expect($data)->toHaveKey('event-data.event', 'bounced');
        expect($data)->toHaveKey('event-data.reason', 'Invalid recipient');
        expect($data)->toHaveKey('event-data.code', '550');
    });

    it('can generate opened data with device info', function () {
        $data = MailgunTestHelper::getOpenedWebhookData();

        expect($data)->toHaveKey('event-data.event', 'opened');
        expect($data)->toHaveKey('event-data.client-info.device-type', 'desktop');
        expect($data)->toHaveKey('event-data.client-info.client-name', 'Chrome');
    });

    it('can process generated data through the service', function () {
        $service = app(MailgunService::class);

        $deliveredData = MailgunTestHelper::getDeliveredWebhookData();
        $request = MailgunTestHelper::createWebhookRequest($deliveredData, 'test-key');
        $result = $service->handleWebhook($request);

        expect($result)->toHaveKey('status', 'success');
        expect($result)->toHaveKey('data.event', 'delivered');

        $inboundData = MailgunTestHelper::getInboundEmailData();
        $request = MailgunTestHelper::createInboundRequest($inboundData);
        $result = $service->handleInbound($request);

        expect($result)->toHaveKey('status', 'success');
        expect($result)->toHaveKey('data.subject', 'Test Email Subject');
        expect($result)->toHaveKey('data.from', 'sender@example.com');
    });

    it('can test webhook endpoints with generated data', function () {
        $webhookData = MailgunTestHelper::getClickedWebhookData();
        $webhookData['event-data']['recipient'] = 'click-test@example.com';
        $webhookRequest = MailgunTestHelper::createWebhookRequest($webhookData, 'test-signing-key');
        $response = $this->postJson(route('mailgun.webhook'), $webhookRequest->all());

        $response->assertStatus(200)
            ->assertJsonPath('data.event', 'clicked')
            ->assertJsonPath('data.recipient', 'click-test@example.com');

        $inboundData = MailgunTestHelper::getInboundEmailData();
        $inboundData['subject'] = 'Helper Test Email';
        $inboundData['body-plain'] = 'Testing the helper';
        $inboundRequest = MailgunTestHelper::createInboundRequest($inboundData);
        $response = $this->postJson('/api/mail/inbound', $inboundRequest->all());

        $response->assertStatus(200)
            ->assertJsonPath('data.subject', 'Helper Test Email')
            ->assertJsonPath('data.body_plain', 'Testing the helper');
    });
});
