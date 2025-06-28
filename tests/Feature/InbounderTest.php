<?php

namespace Fullstack\Inbounder\Tests\Feature;

use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class InbounderTest extends TestCase
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
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup Inbounder config
        $app['config']->set('inbounder.mailgun.signing_key', 'test-signing-key');
        $app['config']->set('inbounder.attachments.max_file_size', 20 * 1024 * 1024);
    }

    /** @test */
    public function it_can_create_inbound_email_model()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test Email',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertInstanceOf(InboundEmail::class, $email);
        $this->assertEquals('<test@example.com>', $email->message_id);
    }

    /** @test */
    public function it_can_create_inbound_email_attachment_model()
    {
        $attachment = InboundEmailAttachment::create([
            'inbound_email_id' => 1,
            'filename' => 'test.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024,
            'file_path' => 'inbound-emails/attachments/2025/06/27/test.pdf',
            'original_name' => 'test.pdf',
            'disposition' => 'attachment',
        ]);

        $this->assertInstanceOf(InboundEmailAttachment::class, $attachment);
        $this->assertEquals('test.pdf', $attachment->filename);
    }

    /** @test */
    public function it_can_format_attachment_size()
    {
        $attachment = new InboundEmailAttachment([
            'size' => 1024 * 1024, // 1MB
        ]);

        $this->assertEquals('1 MB', $attachment->formatted_size);
    }
}
