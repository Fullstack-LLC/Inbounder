<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Inbounder\Events\WebhookEventReceived;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WebhookEventReceivedTest extends TestCase
{
    private array $sampleWebhookData;

    private array $sampleParsedData;

    private WebhookEventReceived $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sampleWebhookData = [
            'event-data' => [
                'event' => 'delivered',
                'timestamp' => 1640995200,
                'message' => [
                    'headers' => [
                        'message-id' => 'test-message-id@example.com',
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
                'reason' => null,
                'code' => null,
                'error' => null,
                'severity' => null,
                'delivery-status' => 'delivered',
                'envelope' => [
                    'sender' => 'sender@example.com',
                    'transport' => 'smtp',
                ],
                'flags' => ['is-routed', 'is-authenticated'],
                'tags' => ['newsletter', 'welcome'],
                'campaigns' => ['welcome-series'],
                'user-variables' => [
                    'user_id' => '123',
                    'campaign_id' => 'welcome-2024',
                ],
            ],
        ];

        $this->sampleParsedData = [
            'event' => 'delivered',
            'timestamp' => '1640995200',
            'message_id' => 'test-message-id@example.com',
            'recipient' => 'test@example.com',
            'domain' => 'example.com',
            'ip' => '192.168.1.1',
            'country' => 'US',
            'region' => 'CA',
            'city' => 'San Francisco',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'device_type' => 'desktop',
            'client_type' => 'browser',
            'client_name' => 'Chrome',
            'client_os' => 'Windows',
            'reason' => null,
            'code' => null,
            'error' => null,
            'severity' => null,
            'delivery_status' => 'delivered',
            'envelope' => [
                'sender' => 'sender@example.com',
                'transport' => 'smtp',
            ],
            'flags' => ['is-routed', 'is-authenticated'],
            'tags' => ['newsletter', 'welcome'],
            'campaigns' => ['welcome-series'],
            'user_variables' => [
                'user_id' => '123',
                'campaign_id' => 'welcome-2024',
            ],
        ];

        $this->event = new WebhookEventReceived(
            'delivered',
            $this->sampleWebhookData,
            $this->sampleParsedData
        );
    }

    #[Test]
    public function it_creates_webhook_event_with_correct_properties()
    {
        $this->assertEquals('delivered', $this->event->eventType);
        $this->assertEquals($this->sampleWebhookData, $this->event->webhookData);
        $this->assertEquals($this->sampleParsedData, $this->event->parsedData);
    }

    #[Test]
    public function it_returns_message_id()
    {
        $this->assertEquals('test-message-id@example.com', $this->event->getMessageId());
    }

    #[Test]
    public function it_returns_recipient()
    {
        $this->assertEquals('test@example.com', $this->event->getRecipient());
    }

    #[Test]
    public function it_returns_domain()
    {
        $this->assertEquals('example.com', $this->event->getDomain());
    }

    #[Test]
    public function it_returns_ip()
    {
        $this->assertEquals('192.168.1.1', $this->event->getIp());
    }

    #[Test]
    public function it_returns_user_agent()
    {
        $this->assertEquals('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', $this->event->getUserAgent());
    }

    #[Test]
    public function it_returns_timestamp()
    {
        $this->assertEquals('1640995200', $this->event->getTimestamp());
    }

    #[Test]
    public function it_returns_reason()
    {
        $this->assertNull($this->event->getReason());
    }

    #[Test]
    public function it_returns_code()
    {
        $this->assertNull($this->event->getCode());
    }

    #[Test]
    public function it_returns_severity()
    {
        $this->assertNull($this->event->getSeverity());
    }

    #[Test]
    public function it_returns_delivery_status()
    {
        $this->assertEquals('delivered', $this->event->getDeliveryStatus());
    }

    #[Test]
    public function it_returns_envelope()
    {
        $expected = [
            'sender' => 'sender@example.com',
            'transport' => 'smtp',
        ];
        $this->assertEquals($expected, $this->event->getEnvelope());
    }

    #[Test]
    public function it_returns_flags()
    {
        $expected = ['is-routed', 'is-authenticated'];
        $this->assertEquals($expected, $this->event->getFlags());
    }

    #[Test]
    public function it_returns_tags()
    {
        $expected = ['newsletter', 'welcome'];
        $this->assertEquals($expected, $this->event->getTags());
    }

    #[Test]
    public function it_returns_campaigns()
    {
        $expected = ['welcome-series'];
        $this->assertEquals($expected, $this->event->getCampaigns());
    }

    #[Test]
    public function it_returns_user_variables()
    {
        $expected = [
            'user_id' => '123',
            'campaign_id' => 'welcome-2024',
        ];
        $this->assertEquals($expected, $this->event->getUserVariables());
    }

    #[Test]
    public function it_returns_geolocation()
    {
        $expected = [
            'country' => 'US',
            'region' => 'CA',
            'city' => 'San Francisco',
        ];
        $this->assertEquals($expected, $this->event->getGeolocation());
    }

    #[Test]
    public function it_returns_client_info()
    {
        $expected = [
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'device_type' => 'desktop',
            'client_type' => 'browser',
            'client_name' => 'Chrome',
            'client_os' => 'Windows',
        ];
        $this->assertEquals($expected, $this->event->getClientInfo());
    }

    #[Test]
    public function it_handles_missing_data_gracefully()
    {
        $event = new WebhookEventReceived('test', [], []);

        $this->assertNull($event->getMessageId());
        $this->assertNull($event->getRecipient());
        $this->assertNull($event->getDomain());
        $this->assertNull($event->getIp());
        $this->assertNull($event->getUserAgent());
        $this->assertNull($event->getTimestamp());
        $this->assertNull($event->getReason());
        $this->assertNull($event->getCode());
        $this->assertNull($event->getSeverity());
        $this->assertNull($event->getDeliveryStatus());
        $this->assertNull($event->getEnvelope());
        $this->assertNull($event->getFlags());
        $this->assertNull($event->getTags());
        $this->assertNull($event->getCampaigns());
        $this->assertNull($event->getUserVariables());
    }

    #[Test]
    public function it_returns_empty_arrays_for_missing_geolocation_and_client_info()
    {
        $event = new WebhookEventReceived('test', [], []);

        $this->assertEquals([
            'country' => null,
            'region' => null,
            'city' => null,
        ], $event->getGeolocation());

        $this->assertEquals([
            'user_agent' => null,
            'device_type' => null,
            'client_type' => null,
            'client_name' => null,
            'client_os' => null,
        ], $event->getClientInfo());
    }

    #[Test]
    public function it_identifies_delivery_events()
    {
        $deliveryEvents = ['delivered', 'bounced', 'complained'];

        foreach ($deliveryEvents as $eventType) {
            $event = new WebhookEventReceived($eventType, [], []);
            $this->assertTrue($event->isDeliveryEvent(), "Event type '{$eventType}' should be identified as delivery event");
        }

        $nonDeliveryEvents = ['opened', 'clicked', 'unsubscribed', 'accepted', 'rejected', 'dropped'];

        foreach ($nonDeliveryEvents as $eventType) {
            $event = new WebhookEventReceived($eventType, [], []);
            $this->assertFalse($event->isDeliveryEvent(), "Event type '{$eventType}' should not be identified as delivery event");
        }
    }

    #[Test]
    public function it_identifies_engagement_events()
    {
        $engagementEvents = ['opened', 'clicked', 'unsubscribed'];

        foreach ($engagementEvents as $eventType) {
            $event = new WebhookEventReceived($eventType, [], []);
            $this->assertTrue($event->isEngagementEvent(), "Event type '{$eventType}' should be identified as engagement event");
        }

        $nonEngagementEvents = ['delivered', 'bounced', 'complained', 'accepted', 'rejected', 'dropped'];

        foreach ($nonEngagementEvents as $eventType) {
            $event = new WebhookEventReceived($eventType, [], []);
            $this->assertFalse($event->isEngagementEvent(), "Event type '{$eventType}' should not be identified as engagement event");
        }
    }

    #[Test]
    public function it_identifies_error_events()
    {
        $errorEvents = ['bounced', 'complained'];

        foreach ($errorEvents as $eventType) {
            $event = new WebhookEventReceived($eventType, [], []);
            $this->assertTrue($event->isErrorEvent(), "Event type '{$eventType}' should be identified as error event");
        }

        $nonErrorEvents = ['delivered', 'opened', 'clicked', 'unsubscribed', 'accepted', 'rejected', 'dropped'];

        foreach ($nonErrorEvents as $eventType) {
            $event = new WebhookEventReceived($eventType, [], []);
            $this->assertFalse($event->isErrorEvent(), "Event type '{$eventType}' should not be identified as error event");
        }
    }

    #[Test]
    public function it_handles_bounce_event_with_reason_and_code()
    {
        $bounceData = [
            'event' => 'bounced',
            'message_id' => 'bounce-message-id@example.com',
            'recipient' => 'bounce@example.com',
            'reason' => 'Mailbox not found',
            'code' => '550',
            'severity' => 'permanent',
        ];

        $event = new WebhookEventReceived('bounced', [], $bounceData);

        $this->assertEquals('bounce-message-id@example.com', $event->getMessageId());
        $this->assertEquals('bounce@example.com', $event->getRecipient());
        $this->assertEquals('Mailbox not found', $event->getReason());
        $this->assertEquals('550', $event->getCode());
        $this->assertEquals('permanent', $event->getSeverity());
        $this->assertTrue($event->isDeliveryEvent());
        $this->assertTrue($event->isErrorEvent());
        $this->assertFalse($event->isEngagementEvent());
    }

    #[Test]
    public function it_handles_opened_event_with_client_info()
    {
        $openedData = [
            'event' => 'opened',
            'message_id' => 'opened-message-id@example.com',
            'recipient' => 'opened@example.com',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)',
            'device_type' => 'mobile',
            'client_type' => 'mobile',
            'client_name' => 'Safari',
            'client_os' => 'iOS',
            'country' => 'UK',
            'region' => 'England',
            'city' => 'London',
        ];

        $event = new WebhookEventReceived('opened', [], $openedData);

        $this->assertEquals('opened-message-id@example.com', $event->getMessageId());
        $this->assertEquals('opened@example.com', $event->getRecipient());
        $this->assertEquals('Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', $event->getUserAgent());
        $this->assertTrue($event->isEngagementEvent());
        $this->assertFalse($event->isDeliveryEvent());
        $this->assertFalse($event->isErrorEvent());

        $clientInfo = $event->getClientInfo();
        $this->assertEquals('mobile', $clientInfo['device_type']);
        $this->assertEquals('mobile', $clientInfo['client_type']);
        $this->assertEquals('Safari', $clientInfo['client_name']);
        $this->assertEquals('iOS', $clientInfo['client_os']);

        $geolocation = $event->getGeolocation();
        $this->assertEquals('UK', $geolocation['country']);
        $this->assertEquals('England', $geolocation['region']);
        $this->assertEquals('London', $geolocation['city']);
    }
}
