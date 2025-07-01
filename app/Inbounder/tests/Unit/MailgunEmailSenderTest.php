<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Inbounder\Mail\TemplatedEmail;
use Inbounder\Models\EmailTemplate;
use Inbounder\Services\MailgunEmailSender;
use Inbounder\Services\MailgunTrackingService;
use Inbounder\Tests\TestCase;

class MailgunEmailSenderTest extends TestCase
{
    use RefreshDatabase;

    private MailgunEmailSender $emailSender;

    private MailgunTrackingService $trackingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackingService = new MailgunTrackingService;
        $this->emailSender = new MailgunEmailSender($this->trackingService);

        // Create a test template
        EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Hello {{name}}',
            'html_content' => '<h1>Welcome {{name}}!</h1>',
            'variables' => ['name'],
            'is_active' => true,
        ]);

        // Configure outbound tracking
        Config::set('mailgun.database.outbound.enabled', true);
    }

    public function test_send_tracked_email()
    {
        Mail::fake();

        $mailable = new TemplatedEmail('test-template', ['name' => 'John']);
        $trackingData = [
            'campaign_id' => 'test-campaign',
            'user_id' => 'user-123',
            'template_name' => 'test-template',
        ];

        $messageId = $this->emailSender->send('recipient@example.com', $mailable, $trackingData);

        $this->assertNotNull($messageId);
        $this->assertStringContainsString('msg_', $messageId);

        Mail::assertSent(TemplatedEmail::class);
    }

    public function test_send_with_tracking_headers()
    {
        Mail::fake();

        $mailable = new TemplatedEmail('test-template', ['name' => 'Jane']);
        $trackingData = [
            'campaign_id' => 'campaign-123',
            'user_id' => 'user-456',
            'template_name' => 'welcome',
        ];

        $messageId = $this->emailSender->sendWithTracking('recipient@example.com', $mailable, $trackingData);

        $this->assertNotNull($messageId);
        Mail::assertSent(TemplatedEmail::class);
    }

    public function test_send_email_without_tracking_when_disabled()
    {
        Config::set('mailgun.database.outbound.enabled', false);

        Mail::fake();

        $mailable = new TemplatedEmail('test-template', ['name' => 'Bob']);
        $trackingData = ['campaign_id' => 'test'];

        $messageId = $this->emailSender->send('recipient@example.com', $mailable, $trackingData);

        $this->assertNotNull($messageId);
        Mail::assertSent(TemplatedEmail::class);

        // Verify that no tracking record was created
        $trackedEmail = $this->emailSender->getEmailByMessageId($messageId);
        $this->assertNull($trackedEmail);
    }

    public function test_get_campaign_stats()
    {
        // Create some outbound emails for testing
        $this->trackingService->createOutboundEmail('msg_1', 'user1@example.com', [
            'campaign_id' => 'campaign-1',
            'user_id' => 'user-1',
        ]);

        $this->trackingService->createOutboundEmail('msg_2', 'user2@example.com', [
            'campaign_id' => 'campaign-1',
            'user_id' => 'user-2',
        ]);

        $stats = $this->emailSender->getCampaignStats('campaign-1');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_sent', $stats);
        $this->assertEquals(2, $stats['total_sent']);
    }

    public function test_get_user_stats()
    {
        // Create some outbound emails for testing
        $this->trackingService->createOutboundEmail('msg_1', 'user@example.com', [
            'campaign_id' => 'campaign-1',
            'user_id' => 'user-123',
        ]);

        $this->trackingService->createOutboundEmail('msg_2', 'user@example.com', [
            'campaign_id' => 'campaign-2',
            'user_id' => 'user-123',
        ]);

        $stats = $this->emailSender->getUserStats('user-123');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_sent', $stats);
        $this->assertEquals(2, $stats['total_sent']);
    }

    public function test_get_email_by_message_id()
    {
        $messageId = 'msg_test_123';
        $this->trackingService->createOutboundEmail($messageId, 'test@example.com', [
            'campaign_id' => 'test-campaign',
            'user_id' => 'user-123',
        ]);

        $email = $this->emailSender->getEmailByMessageId($messageId);

        $this->assertNotNull($email);
        $this->assertEquals($messageId, $email->message_id);
        $this->assertEquals('test@example.com', $email->recipient);
    }

    public function test_get_email_by_message_id_returns_null_when_not_found()
    {
        $email = $this->emailSender->getEmailByMessageId('non-existent-message-id');

        $this->assertNull($email);
    }

    public function test_send_to_multiple_recipients()
    {
        Mail::fake();

        $mailable = new TemplatedEmail('test-template', ['name' => 'Multiple']);
        $recipients = ['user1@example.com', 'user2@example.com'];

        $messageId = $this->emailSender->send($recipients, $mailable, [
            'campaign_id' => 'multi-campaign',
        ]);

        $this->assertNotNull($messageId);
        Mail::assertSent(TemplatedEmail::class, 1); // One mailable sent to multiple recipients
    }

    public function test_tracking_service_integration()
    {
        Mail::fake();

        $mailable = new TemplatedEmail('test-template', ['name' => 'Tracked']);
        $trackingData = [
            'campaign_id' => 'tracking-test',
            'user_id' => 'user-tracked',
            'template_name' => 'test-template',
            'from_address' => 'sender@example.com',
            'from_name' => 'Test Sender',
        ];

        $messageId = $this->emailSender->send('tracked@example.com', $mailable, $trackingData);

        // Verify the email was tracked
        $trackedEmail = $this->emailSender->getEmailByMessageId($messageId);
        $this->assertNotNull($trackedEmail);
        $this->assertEquals('tracked@example.com', $trackedEmail->recipient);
        $this->assertEquals('tracking-test', $trackedEmail->campaign_id);
        $this->assertEquals('user-tracked', $trackedEmail->user_id);
    }
}
