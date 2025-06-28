<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Fullstack\Inbounder\Events\InboundEmailFailed;
use Fullstack\Inbounder\Events\InboundEmailProcessed;
use Fullstack\Inbounder\Events\InboundEmailReceived;
use Fullstack\Inbounder\Models\InboundEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class EventsTest extends TestCase
{
    use RefreshDatabase;

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
    }

    /** @test */
    public function it_creates_inbound_email_received_event()
    {
        $emailData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
        ];

        $attachments = [
            [
                'filename' => 'test.pdf',
                'content_type' => 'application/pdf',
                'size' => 1024,
            ],
        ];

        $requestData = [
            'from' => 'sender@example.com',
            'To' => 'recipient@example.com',
            'subject' => 'Test Email',
        ];

        $event = new InboundEmailReceived($emailData, $attachments, $requestData);

        $this->assertInstanceOf(InboundEmailReceived::class, $event);
        $this->assertEquals($emailData, $event->emailData);
        $this->assertEquals($attachments, $event->attachments);
        $this->assertEquals($requestData, $event->requestData);
    }

    /** @test */
    public function it_creates_inbound_email_processed_event()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $attachments = [
            [
                'filename' => 'test.pdf',
                'content_type' => 'application/pdf',
                'size' => 1024,
            ],
        ];

        $event = new InboundEmailProcessed($email, $attachments);

        $this->assertInstanceOf(InboundEmailProcessed::class, $event);
        $this->assertEquals($email, $event->email);
        $this->assertEquals($attachments, $event->attachments);
    }

    /** @test */
    public function it_creates_inbound_email_failed_event()
    {
        $emailData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
        ];

        $errorMessage = 'Processing failed due to invalid data';
        $requestData = [
            'from' => 'sender@example.com',
            'To' => 'recipient@example.com',
            'subject' => 'Test Email',
        ];

        $event = new InboundEmailFailed($emailData, $errorMessage, $requestData);

        $this->assertInstanceOf(InboundEmailFailed::class, $event);
        $this->assertEquals($emailData, $event->emailData);
        $this->assertEquals($errorMessage, $event->error);
        $this->assertEquals($requestData, $event->requestData);
    }

    /** @test */
    public function it_handles_empty_attachments_in_received_event()
    {
        $emailData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
        ];

        $attachments = [];
        $requestData = ['from' => 'sender@example.com'];

        $event = new InboundEmailReceived($emailData, $attachments, $requestData);

        $this->assertEmpty($event->attachments);
        $this->assertEquals($emailData, $event->emailData);
    }

    /** @test */
    public function it_handles_empty_attachments_in_processed_event()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $attachments = [];

        $event = new InboundEmailProcessed($email, $attachments);

        $this->assertEmpty($event->attachments);
        $this->assertEquals($email, $event->email);
    }

    /** @test */
    public function it_handles_empty_request_data_in_received_event()
    {
        $emailData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
        ];

        $attachments = [];
        $requestData = [];

        $event = new InboundEmailReceived($emailData, $attachments, $requestData);

        $this->assertEmpty($event->requestData);
        $this->assertEquals($emailData, $event->emailData);
    }

    /** @test */
    public function it_handles_empty_request_data_in_failed_event()
    {
        $emailData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
        ];

        $errorMessage = 'Processing failed';
        $requestData = [];

        $event = new InboundEmailFailed($emailData, $errorMessage, $requestData);

        $this->assertEmpty($event->requestData);
        $this->assertEquals($errorMessage, $event->error);
    }

    /** @test */
    public function it_handles_complex_email_data_in_received_event()
    {
        $emailData = [
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
            'attachments_count' => 1,
            'size' => 1024,
        ];

        $attachments = [
            [
                'filename' => 'document.pdf',
                'content_type' => 'application/pdf',
                'size' => 1024,
                'original_name' => 'document.pdf',
                'disposition' => 'attachment',
            ],
            [
                'filename' => 'image.jpg',
                'content_type' => 'image/jpeg',
                'size' => 2048,
                'original_name' => 'image.jpg',
                'disposition' => 'inline',
            ],
        ];

        $requestData = [
            'from' => 'John Doe <sender@example.com>',
            'To' => 'Jane Smith <recipient@example.com>',
            'subject' => 'Test Email',
            'body-plain' => 'Test body',
            'body-html' => '<p>Test body</p>',
            'attachment-count' => 2,
        ];

        $event = new InboundEmailReceived($emailData, $attachments, $requestData);

        $this->assertEquals($emailData, $event->emailData);
        $this->assertEquals($attachments, $event->attachments);
        $this->assertEquals($requestData, $event->requestData);
        $this->assertCount(2, $event->attachments);
    }

    /** @test */
    public function it_handles_complex_error_message_in_failed_event()
    {
        $emailData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
        ];

        $errorMessage = 'User with email sender@example.com not found. Please ensure the sender is registered in the system.';
        $requestData = [
            'from' => 'sender@example.com',
            'To' => 'recipient@example.com',
            'subject' => 'Test Email',
        ];

        $event = new InboundEmailFailed($emailData, $errorMessage, $requestData);

        $this->assertEquals($errorMessage, $event->error);
        $this->assertStringContainsString('User with email sender@example.com not found', $event->error);
    }
}
