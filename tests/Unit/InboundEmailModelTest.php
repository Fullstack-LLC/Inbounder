<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Fullstack\Inbounder\Tests\Helpers\MockUser;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class InboundEmailModelTest extends TestCase
{
    use DatabaseMigrations;

    protected function getPackageProviders($app)
    {
        return [
            \Fullstack\Inbounder\InbounderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('inbounder.models.user', MockUser::class);
        $app['config']->set('inbounder.models.tenant', MockTenant::class);
    }

    /** @test */
    public function it_can_create_inbound_email()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'from_name' => 'John Doe',
            'to_email' => 'recipient@example.com',
            'to_name' => 'Jane Smith',
            'subject' => 'Test Email',
            'body_plain' => 'Test body',
            'body_html' => '<p>Test body</p>',
            'stripped_text' => 'Stripped text',
            'stripped_html' => '<p>Stripped HTML</p>',
            'stripped_signature' => 'Signature',
            'sender_id' => 1,
            'tenant_id' => 1,
            'recipient_count' => 1,
            'timestamp' => now(),
            'token' => 'test-token',
            'signature' => 'test-signature',
            'domain' => 'example.com',
            'message_headers' => ['header' => 'value'],
            'envelope' => ['from' => 'sender@example.com'],
            'attachments_count' => 0,
            'size' => 1024,
        ]);

        $this->assertInstanceOf(InboundEmail::class, $email);
        $this->assertEquals('<test@example.com>', $email->message_id);
        $this->assertEquals('sender@example.com', $email->from_email);
        $this->assertEquals('John Doe', $email->from_name);
        $this->assertEquals('recipient@example.com', $email->to_email);
        $this->assertEquals('Jane Smith', $email->to_name);
        $this->assertEquals('Test Email', $email->subject);
        $this->assertEquals('Test body', $email->body_plain);
        $this->assertEquals('<p>Test body</p>', $email->body_html);
        $this->assertEquals('Stripped text', $email->stripped_text);
        $this->assertEquals('<p>Stripped HTML</p>', $email->stripped_html);
        $this->assertEquals('Signature', $email->stripped_signature);
        $this->assertEquals(1, $email->sender_id);
        $this->assertEquals(1, $email->tenant_id);
        $this->assertEquals(1, $email->recipient_count);
        $this->assertEquals('test-token', $email->token);
        $this->assertEquals('test-signature', $email->signature);
        $this->assertEquals('example.com', $email->domain);
        $this->assertEquals(['header' => 'value'], $email->message_headers);
        $this->assertEquals(['from' => 'sender@example.com'], $email->envelope);
        $this->assertEquals(0, $email->attachments_count);
        $this->assertEquals(1024, $email->size);
    }

    /** @test */
    public function it_casts_arrays_correctly()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
            'message_headers' => ['header' => 'value'],
            'envelope' => ['from' => 'sender@example.com'],
            'attachments_count' => 5,
            'size' => 2048,
        ]);

        $this->assertIsArray($email->message_headers);
        $this->assertIsArray($email->envelope);
        $this->assertIsInt($email->attachments_count);
        $this->assertIsInt($email->size);
    }

    /** @test */
    public function it_has_attachments_relationship()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $attachment = InboundEmailAttachment::create([
            'inbound_email_id' => $email->id,
            'filename' => 'test.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024,
            'file_path' => 'path/to/file.pdf',
            'original_name' => 'test.pdf',
            'disposition' => 'attachment',
        ]);

        $this->assertTrue($email->attachments->contains($attachment));
        $this->assertEquals(1, $email->attachments->count());
    }

    /** @test */
    public function it_has_sender_relationship()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $relationship = $email->sender();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
    }

    /** @test */
    public function it_has_tenant_relationship()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $relationship = $email->tenant();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relationship);
    }

    /** @test */
    public function it_filters_by_sender()
    {
        InboundEmail::create([
            'message_id' => '<test1@example.com>',
            'from_email' => 'sender1@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test 1',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        InboundEmail::create([
            'message_id' => '<test2@example.com>',
            'from_email' => 'sender2@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test 2',
            'sender_id' => 2,
            'tenant_id' => 1,
        ]);

        $emails = InboundEmail::bySender(1)->get();
        $this->assertEquals(1, $emails->count());
        $this->assertEquals('sender1@example.com', $emails->first()->from_email);
    }

    /** @test */
    public function it_filters_by_tenant()
    {
        InboundEmail::create([
            'message_id' => '<test1@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test 1',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        InboundEmail::create([
            'message_id' => '<test2@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test 2',
            'sender_id' => 1,
            'tenant_id' => 2,
        ]);

        $emails = InboundEmail::byTenant(1)->get();
        $this->assertEquals(1, $emails->count());
        $this->assertEquals(1, $emails->first()->tenant_id);
    }

    /** @test */
    public function it_filters_by_message_id()
    {
        InboundEmail::create([
            'message_id' => '<test1@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test 1',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        InboundEmail::create([
            'message_id' => '<test2@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test 2',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $email = InboundEmail::byMessageId('<test1@example.com>')->first();
        $this->assertNotNull($email);
        $this->assertEquals('<test1@example.com>', $email->message_id);
    }

    /** @test */
    public function it_uses_configurable_model_classes()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertEquals(MockUser::class, get_class($email->sender()->getRelated()));
        $this->assertEquals(MockTenant::class, get_class($email->tenant()->getRelated()));
    }

    /** @test */
    public function it_handles_multiple_recipients()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'primary@example.com',
            'to_name' => 'Primary Recipient',
            'to_emails' => ['primary@example.com', 'secondary@example.com'],
            'cc_emails' => ['cc1@example.com', 'cc2@example.com'],
            'bcc_emails' => ['bcc@example.com'],
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertIsArray($email->to_emails);
        $this->assertIsArray($email->cc_emails);
        $this->assertIsArray($email->bcc_emails);
        $this->assertEquals(['primary@example.com', 'secondary@example.com'], $email->to_emails);
        $this->assertEquals(['cc1@example.com', 'cc2@example.com'], $email->cc_emails);
        $this->assertEquals(['bcc@example.com'], $email->bcc_emails);
    }

    /** @test */
    public function it_gets_all_recipients()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'primary@example.com',
            'to_emails' => ['primary@example.com', 'secondary@example.com'],
            'cc_emails' => ['cc1@example.com', 'cc2@example.com'],
            'bcc_emails' => ['bcc@example.com'],
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $allRecipients = $email->getAllRecipients();
        $expectedRecipients = [
            'primary@example.com',
            'secondary@example.com',
            'cc1@example.com',
            'cc2@example.com',
            'bcc@example.com'
        ];

        $this->assertEquals($expectedRecipients, $allRecipients);
        $this->assertEquals(5, $email->getTotalRecipientCount());
    }

    /** @test */
    public function it_gets_primary_recipient()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'fallback@example.com',
            'to_emails' => ['primary@example.com', 'secondary@example.com'],
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertEquals('primary@example.com', $email->getPrimaryRecipient());
    }

    /** @test */
    public function it_falls_back_to_original_to_email_for_primary_recipient()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'fallback@example.com',
            'to_emails' => null,
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertEquals('fallback@example.com', $email->getPrimaryRecipient());
    }

    /** @test */
    public function it_checks_if_email_is_recipient()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'primary@example.com',
            'to_emails' => ['primary@example.com', 'secondary@example.com'],
            'cc_emails' => ['cc@example.com'],
            'bcc_emails' => ['bcc@example.com'],
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertTrue($email->isRecipient('primary@example.com'));
        $this->assertTrue($email->isRecipient('secondary@example.com'));
        $this->assertTrue($email->isRecipient('cc@example.com'));
        $this->assertTrue($email->isRecipient('bcc@example.com'));
        $this->assertFalse($email->isRecipient('not-a-recipient@example.com'));
    }

    /** @test */
    public function it_handles_empty_recipient_arrays()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'primary@example.com',
            'to_emails' => [],
            'cc_emails' => null,
            'bcc_emails' => [],
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertEquals(['primary@example.com'], $email->getAllRecipients());
        $this->assertEquals(1, $email->getTotalRecipientCount());
        $this->assertEquals('primary@example.com', $email->getPrimaryRecipient());
    }

    /** @test */
    public function it_returns_delivered_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(1, $email->delivered());
    }

    /** @test */
    public function it_returns_opened_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(1, $email->opened());
    }

    /** @test */
    public function it_returns_clicked_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(1, $email->clicked());
    }

    /** @test */
    public function it_returns_bounced_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0, $email->bounced());
    }

    /** @test */
    public function it_returns_dropped_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0, $email->dropped());
    }

    /** @test */
    public function it_returns_complained_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0, $email->complained());
    }

    /** @test */
    public function it_returns_unsubscribed_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0, $email->unsubscribed());
    }

    /** @test */
    public function it_returns_failed_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0, $email->failed());

        // Add a bounced event
        InboundEmailEvent::create([
            'inbound_email_id' => $email->id,
            'event_type' => 'bounced',
            'occurred_at' => Carbon::now(),
        ]);

        $this->assertEquals(1, $email->failed());
    }

    /** @test */
    public function it_returns_engaged_count()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(2, $email->engaged()); // opened + clicked
    }

    /** @test */
    public function it_checks_if_email_was_delivered()
    {
        $email = $this->createEmailWithEvents();

        $this->assertTrue($email->wasDelivered());
    }

    /** @test */
    public function it_checks_if_email_was_opened()
    {
        $email = $this->createEmailWithEvents();

        $this->assertTrue($email->wasOpened());
    }

    /** @test */
    public function it_checks_if_email_was_clicked()
    {
        $email = $this->createEmailWithEvents();

        $this->assertTrue($email->wasClicked());
    }

    /** @test */
    public function it_checks_if_email_has_failed()
    {
        $email = $this->createEmailWithEvents();

        $this->assertFalse($email->hasFailed());

        // Add a bounced event
        InboundEmailEvent::create([
            'inbound_email_id' => $email->id,
            'event_type' => 'bounced',
            'occurred_at' => Carbon::now(),
        ]);

        $this->assertTrue($email->hasFailed());
    }

    /** @test */
    public function it_calculates_open_rate()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(100.0, $email->getOpenRate()); // 1 opened / 1 recipient
    }

    /** @test */
    public function it_calculates_click_rate()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(100.0, $email->getClickRate()); // 1 clicked / 1 recipient
    }

    /** @test */
    public function it_calculates_bounce_rate()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0.0, $email->getBounceRate());

        // Add a bounced event
        InboundEmailEvent::create([
            'inbound_email_id' => $email->id,
            'event_type' => 'bounced',
            'occurred_at' => Carbon::now(),
        ]);

        $this->assertEquals(100.0, $email->getBounceRate()); // 1 bounced / 1 recipient
    }

    /** @test */
    public function it_calculates_failure_rate()
    {
        $email = $this->createEmailWithEvents();

        $this->assertEquals(0.0, $email->getFailureRate());

        // Add a bounced event
        InboundEmailEvent::create([
            'inbound_email_id' => $email->id,
            'event_type' => 'bounced',
            'occurred_at' => Carbon::now(),
        ]);

        $this->assertEquals(100.0, $email->getFailureRate()); // 1 failed / 1 recipient
    }

    /** @test */
    public function it_returns_comprehensive_stats()
    {
        $email = $this->createEmailWithEvents();

        $stats = $email->stats();

        $this->assertArrayHasKey('recipient_count', $stats);
        $this->assertArrayHasKey('events', $stats);
        $this->assertArrayHasKey('rates', $stats);
        $this->assertArrayHasKey('status', $stats);
        $this->assertArrayHasKey('first_event_at', $stats);
        $this->assertArrayHasKey('last_event_at', $stats);
        $this->assertArrayHasKey('total_events', $stats);

        $this->assertEquals(1, $stats['recipient_count']);
        $this->assertEquals(3, $stats['total_events']);
        $this->assertEquals(1, $stats['events']['delivered']);
        $this->assertEquals(1, $stats['events']['opened']);
        $this->assertEquals(1, $stats['events']['clicked']);
        $this->assertEquals(100.0, $stats['rates']['open_rate']);
        $this->assertEquals(100.0, $stats['rates']['click_rate']);
        $this->assertTrue($stats['status']['was_delivered']);
        $this->assertTrue($stats['status']['was_opened']);
        $this->assertTrue($stats['status']['was_clicked']);
        $this->assertFalse($stats['status']['failed']);
    }

    /** @test */
    public function it_handles_zero_recipients_correctly()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertEquals(0.0, $email->getOpenRate());
        $this->assertEquals(0.0, $email->getClickRate());
        $this->assertEquals(0.0, $email->getBounceRate());
        $this->assertEquals(0.0, $email->getFailureRate());
    }

    /** @test */
    public function it_returns_events_by_type()
    {
        $email = $this->createEmailWithEvents();

        $deliveredEvents = $email->getEventsByType('delivered');
        $this->assertEquals(1, $deliveredEvents->count());

        $openedEvents = $email->getEventsByType('opened');
        $this->assertEquals(1, $openedEvents->count());
    }

    /** @test */
    public function it_returns_first_event_of_type()
    {
        $email = $this->createEmailWithEvents();

        $firstDelivered = $email->getFirstEvent('delivered');
        $this->assertNotNull($firstDelivered);
        $this->assertEquals('delivered', $firstDelivered->event_type);

        $firstBounced = $email->getFirstEvent('bounced');
        $this->assertNull($firstBounced);
    }

    /** @test */
    public function it_returns_last_event_of_type()
    {
        $email = $this->createEmailWithEvents();

        // Add another delivered event
        InboundEmailEvent::create([
            'inbound_email_id' => $email->id,
            'event_type' => 'delivered',
            'occurred_at' => Carbon::now()->addMinutes(5),
        ]);

        $lastDelivered = $email->getLastEvent('delivered');
        $this->assertNotNull($lastDelivered);
        $this->assertEquals('delivered', $lastDelivered->event_type);
    }

    /** @test */
    public function it_returns_geographic_events()
    {
        $email = $this->createEmailWithEvents();

        $geographicEvents = $email->getGeographicEvents();
        $this->assertEquals(3, $geographicEvents->count());

        foreach ($geographicEvents as $event) {
            $this->assertNotNull($event->country);
        }
    }

    /** @test */
    public function it_returns_device_events()
    {
        $email = $this->createEmailWithEvents();

        $deviceEvents = $email->getDeviceEvents();
        $this->assertEquals(3, $deviceEvents->count());

        foreach ($deviceEvents as $event) {
            $this->assertNotNull($event->device_type);
        }
    }

    /** @test */
    public function it_calculates_total_recipient_count()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'to_emails' => ['recipient1@example.com', 'recipient2@example.com'],
            'cc_emails' => ['cc1@example.com'],
            'bcc_emails' => ['bcc1@example.com', 'bcc2@example.com'],
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertEquals(5, $email->getTotalRecipientCount()); // 2 to + 1 cc + 2 bcc (original to_email is not counted separately)
    }

    private function createEmailWithEvents(): InboundEmail
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        // Create test events
        $events = [
            [
                'event_type' => 'delivered',
                'ip_address' => '192.168.1.1',
                'country' => 'United States',
                'region' => 'California',
                'city' => 'San Francisco',
                'device_type' => 'desktop',
                'client_type' => 'webmail',
                'client_name' => 'Gmail',
                'occurred_at' => Carbon::now()->subMinutes(10),
            ],
            [
                'event_type' => 'opened',
                'ip_address' => '192.168.1.2',
                'country' => 'United States',
                'region' => 'New York',
                'city' => 'New York',
                'device_type' => 'mobile',
                'client_type' => 'mobile',
                'client_name' => 'iPhone Mail',
                'occurred_at' => Carbon::now()->subMinutes(5),
            ],
            [
                'event_type' => 'clicked',
                'ip_address' => '192.168.1.3',
                'country' => 'United States',
                'region' => 'Texas',
                'city' => 'Austin',
                'device_type' => 'tablet',
                'client_type' => 'webmail',
                'client_name' => 'Outlook',
                'occurred_at' => Carbon::now()->subMinutes(2),
            ],
        ];

        foreach ($events as $eventData) {
            InboundEmailEvent::create(array_merge($eventData, [
                'inbound_email_id' => $email->id,
            ]));
        }

        return $email;
    }
}
