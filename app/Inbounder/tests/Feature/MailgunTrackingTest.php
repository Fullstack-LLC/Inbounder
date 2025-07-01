<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Models\MailgunOutboundEmail;
use Inbounder\Services\MailgunTrackingService;
use Inbounder\Tests\TestCase;

/**
 * Test outbound email tracking functionality.
 */
class MailgunTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mailgun.database.outbound.enabled' => true]);
    }

    /**
     * Test creating an outbound email record.
     */
    public function test_can_create_outbound_email_record(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        $data = [
            'from_address' => 'test@example.com',
            'from_name' => 'Test Sender',
            'subject' => 'Test Subject',
            'template_name' => 'welcome-email',
            'campaign_id' => 'test-campaign',
            'user_id' => 'user-123',
            'metadata' => ['source' => 'registration'],
        ];

        $outboundEmail = $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            $data
        );

        $this->assertInstanceOf(MailgunOutboundEmail::class, $outboundEmail);
        $this->assertEquals('msg_test123@example.com', $outboundEmail->getMessageId());
        $this->assertEquals('recipient@example.com', $outboundEmail->getRecipient());
        $this->assertEquals('test@example.com', $outboundEmail->getFromAddress());
        $this->assertEquals('Test Sender', $outboundEmail->getFromName());
        $this->assertEquals('Test Subject', $outboundEmail->getSubject());
        $this->assertEquals('welcome-email', $outboundEmail->getTemplateName());
        $this->assertEquals('test-campaign', $outboundEmail->getCampaignId());
        $this->assertEquals('user-123', $outboundEmail->getUserId());
        $this->assertEquals(['source' => 'registration'], $outboundEmail->getMetadata());
        $this->assertEquals('sent', $outboundEmail->getStatus());
    }

    /**
     * Test updating outbound email from webhook event.
     */
    public function test_can_update_outbound_email_from_webhook(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create initial email record
        $outboundEmail = $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            ['subject' => 'Test Subject']
        );

        // Update with delivered event
        $updatedEmail = $trackingService->updateFromWebhook(
            'msg_test123@example.com',
            'delivered',
            ['timestamp' => time()]
        );

        $this->assertNotNull($updatedEmail);
        $this->assertEquals('delivered', $updatedEmail->getStatus());
        $this->assertNotNull($updatedEmail->getDeliveredAt());
    }

    /**
     * Test updating outbound email with opened event.
     */
    public function test_can_update_outbound_email_with_opened_event(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create initial email record
        $outboundEmail = $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            ['subject' => 'Test Subject']
        );

        // Update with opened event
        $updatedEmail = $trackingService->updateFromWebhook(
            'msg_test123@example.com',
            'opened',
            ['timestamp' => time()]
        );

        $this->assertNotNull($updatedEmail);
        $this->assertEquals('opened', $updatedEmail->getStatus());
        $this->assertNotNull($updatedEmail->getOpenedAt());
    }

    /**
     * Test updating outbound email with clicked event.
     */
    public function test_can_update_outbound_email_with_clicked_event(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create initial email record
        $outboundEmail = $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            ['subject' => 'Test Subject']
        );

        // Update with clicked event
        $updatedEmail = $trackingService->updateFromWebhook(
            'msg_test123@example.com',
            'clicked',
            ['timestamp' => time()]
        );

        $this->assertNotNull($updatedEmail);
        $this->assertEquals('clicked', $updatedEmail->getStatus());
        $this->assertNotNull($updatedEmail->getClickedAt());
    }

    /**
     * Test updating outbound email with bounced event.
     */
    public function test_can_update_outbound_email_with_bounced_event(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create initial email record
        $outboundEmail = $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            ['subject' => 'Test Subject']
        );

        // Update with bounced event
        $updatedEmail = $trackingService->updateFromWebhook(
            'msg_test123@example.com',
            'bounced',
            ['timestamp' => time()]
        );

        $this->assertNotNull($updatedEmail);
        $this->assertEquals('bounced', $updatedEmail->getStatus());
        $this->assertNotNull($updatedEmail->getBouncedAt());
    }

    /**
     * Test getting outbound email by message ID.
     */
    public function test_can_get_outbound_email_by_message_id(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create email record
        $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            ['subject' => 'Test Subject']
        );

        // Retrieve by message ID
        $email = $trackingService->getByMessageId('msg_test123@example.com');

        $this->assertNotNull($email);
        $this->assertEquals('msg_test123@example.com', $email->getMessageId());
        $this->assertEquals('recipient@example.com', $email->getRecipient());
    }

    /**
     * Test getting outbound emails by recipient.
     */
    public function test_can_get_outbound_emails_by_recipient(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create multiple email records
        $trackingService->createOutboundEmail('msg1@example.com', 'user@example.com', ['subject' => 'Email 1']);
        $trackingService->createOutboundEmail('msg2@example.com', 'user@example.com', ['subject' => 'Email 2']);
        $trackingService->createOutboundEmail('msg3@example.com', 'other@example.com', ['subject' => 'Email 3']);

        // Get emails for specific recipient
        $emails = $trackingService->getByRecipient('user@example.com');

        $this->assertCount(2, $emails);
        $this->assertEquals('user@example.com', $emails[0]->getRecipient());
        $this->assertEquals('user@example.com', $emails[1]->getRecipient());
    }

    /**
     * Test getting outbound emails by campaign.
     */
    public function test_can_get_outbound_emails_by_campaign(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create multiple email records
        $trackingService->createOutboundEmail('msg1@example.com', 'user1@example.com', ['campaign_id' => 'campaign-1']);
        $trackingService->createOutboundEmail('msg2@example.com', 'user2@example.com', ['campaign_id' => 'campaign-1']);
        $trackingService->createOutboundEmail('msg3@example.com', 'user3@example.com', ['campaign_id' => 'campaign-2']);

        // Get emails for specific campaign
        $emails = $trackingService->getByCampaign('campaign-1');

        $this->assertCount(2, $emails);
        $this->assertEquals('campaign-1', $emails[0]->getCampaignId());
        $this->assertEquals('campaign-1', $emails[1]->getCampaignId());
    }

    /**
     * Test getting campaign statistics.
     */
    public function test_can_get_campaign_statistics(): void
    {
        $trackingService = app(\Inbounder\Services\MailgunTrackingService::class);

        // Create email records with different statuses
        $trackingService->createOutboundEmail('msg1@example.com', 'user1@example.com', ['campaign_id' => 'campaign-1']);
        $trackingService->createOutboundEmail('msg2@example.com', 'user2@example.com', ['campaign_id' => 'campaign-1']);
        $trackingService->createOutboundEmail('msg3@example.com', 'user3@example.com', ['campaign_id' => 'campaign-1']);

        // Update some emails with events
        $trackingService->updateFromWebhook('msg1@example.com', 'delivered', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg2@example.com', 'delivered', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg2@example.com', 'opened', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg3@example.com', 'bounced', ['timestamp' => time()]);

        // Create event log entries
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'delivered', 'message_id' => 'msg1@example.com', 'recipient' => 'user1@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'delivered', 'message_id' => 'msg2@example.com', 'recipient' => 'user2@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'opened', 'message_id' => 'msg2@example.com', 'recipient' => 'user2@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'bounced', 'message_id' => 'msg3@example.com', 'recipient' => 'user3@example.com', 'raw_data' => []]);

        // Get cumulative campaign statistics
        $cumulativeStats = $trackingService->getCumulativeCampaignStats('campaign-1');

        $this->assertEquals(3, $cumulativeStats['total_sent']);
        $this->assertEquals(2, $cumulativeStats['delivered']);
        $this->assertEquals(1, $cumulativeStats['opened']);
        $this->assertEquals(1, $cumulativeStats['bounced']);
    }

    /**
     * Test getting user statistics.
     */
    public function test_can_get_user_statistics(): void
    {
        $trackingService = app(\Inbounder\Services\MailgunTrackingService::class);

        // Create email records for a user
        $trackingService->createOutboundEmail('msg1@example.com', 'user@example.com', ['user_id' => 'user-123']);
        $trackingService->createOutboundEmail('msg2@example.com', 'user@example.com', ['user_id' => 'user-123']);

        // Update some emails with events
        $trackingService->updateFromWebhook('msg1@example.com', 'delivered', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg1@example.com', 'opened', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg2@example.com', 'bounced', ['timestamp' => time()]);

        // Create event log entries
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'delivered', 'message_id' => 'msg1@example.com', 'recipient' => 'user@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'opened', 'message_id' => 'msg1@example.com', 'recipient' => 'user@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'bounced', 'message_id' => 'msg2@example.com', 'recipient' => 'user@example.com', 'raw_data' => []]);

        // Get cumulative user statistics
        $cumulativeStats = $trackingService->getCumulativeUserStats('user-123');

        $this->assertEquals(2, $cumulativeStats['total_sent']);
        $this->assertEquals(1, $cumulativeStats['delivered']);
        $this->assertEquals(1, $cumulativeStats['opened']);
        $this->assertEquals(1, $cumulativeStats['bounced']);
    }

    /**
     * Test that outbound email has relationship to events.
     */
    public function test_outbound_email_has_events_relationship(): void
    {
        $trackingService = app(MailgunTrackingService::class);

        // Create email record
        $outboundEmail = $trackingService->createOutboundEmail(
            'msg_test123@example.com',
            'recipient@example.com',
            ['subject' => 'Test Subject']
        );

        // Create a webhook event (this would normally be done by the webhook handler)
        $event = \Inbounder\Models\MailgunEvent::create([
            'event_type' => 'delivered',
            'message_id' => 'msg_test123@example.com',
            'recipient' => 'recipient@example.com',
            'raw_data' => ['event' => 'delivered'],
        ]);

        // Test the relationship
        $this->assertCount(1, $outboundEmail->events);
        $this->assertEquals('delivered', $outboundEmail->events->first()->getEventType());
    }

    /**
     * Test getting cumulative campaign statistics.
     */
    public function test_can_get_cumulative_campaign_statistics(): void
    {
        $trackingService = app(\Inbounder\Services\MailgunTrackingService::class);

        // Create email records with different statuses
        $trackingService->createOutboundEmail('msg1@example.com', 'user1@example.com', ['campaign_id' => 'campaign-1']);
        $trackingService->createOutboundEmail('msg2@example.com', 'user2@example.com', ['campaign_id' => 'campaign-1']);
        $trackingService->createOutboundEmail('msg3@example.com', 'user3@example.com', ['campaign_id' => 'campaign-1']);

        // Update some emails with events
        $trackingService->updateFromWebhook('msg1@example.com', 'delivered', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg2@example.com', 'delivered', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg2@example.com', 'opened', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg3@example.com', 'bounced', ['timestamp' => time()]);

        // Create event log entries
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'delivered', 'message_id' => 'msg1@example.com', 'recipient' => 'user1@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'delivered', 'message_id' => 'msg2@example.com', 'recipient' => 'user2@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'opened', 'message_id' => 'msg2@example.com', 'recipient' => 'user2@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'bounced', 'message_id' => 'msg3@example.com', 'recipient' => 'user3@example.com', 'raw_data' => []]);

        // Get cumulative campaign statistics
        $cumulativeStats = $trackingService->getCumulativeCampaignStats('campaign-1');

        $this->assertEquals(3, $cumulativeStats['total_sent']);
        $this->assertEquals(2, $cumulativeStats['delivered']);
        $this->assertEquals(1, $cumulativeStats['opened']);
        $this->assertEquals(1, $cumulativeStats['bounced']);
    }

    /**
     * Test getting cumulative user statistics.
     */
    public function test_can_get_cumulative_user_statistics(): void
    {
        $trackingService = app(\Inbounder\Services\MailgunTrackingService::class);

        // Create email records for a user
        $trackingService->createOutboundEmail('msg1@example.com', 'user@example.com', ['user_id' => 'user-123']);
        $trackingService->createOutboundEmail('msg2@example.com', 'user@example.com', ['user_id' => 'user-123']);

        // Update some emails with events
        $trackingService->updateFromWebhook('msg1@example.com', 'delivered', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg1@example.com', 'opened', ['timestamp' => time()]);
        $trackingService->updateFromWebhook('msg2@example.com', 'bounced', ['timestamp' => time()]);

        // Create event log entries
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'delivered', 'message_id' => 'msg1@example.com', 'recipient' => 'user@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'opened', 'message_id' => 'msg1@example.com', 'recipient' => 'user@example.com', 'raw_data' => []]);
        \Inbounder\Models\MailgunEvent::create(['event_type' => 'bounced', 'message_id' => 'msg2@example.com', 'recipient' => 'user@example.com', 'raw_data' => []]);

        // Get cumulative user statistics
        $cumulativeStats = $trackingService->getCumulativeUserStats('user-123');

        $this->assertEquals(2, $cumulativeStats['total_sent']);
        $this->assertEquals(1, $cumulativeStats['delivered']);
        $this->assertEquals(1, $cumulativeStats['opened']);
        $this->assertEquals(1, $cumulativeStats['bounced']);
    }
}
