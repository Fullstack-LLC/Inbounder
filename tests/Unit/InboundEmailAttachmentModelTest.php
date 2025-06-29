<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Fullstack\Inbounder\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Storage;

class InboundEmailAttachmentModelTest extends TestCase
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
    }

    /** @test */
    public function it_can_create_inbound_email_attachment()
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
            'file_path' => 'inbound-emails/attachments/2025/06/27/test.pdf',
            'original_name' => 'test.pdf',
            'disposition' => 'attachment',
        ]);

        $this->assertInstanceOf(InboundEmailAttachment::class, $attachment);
        $this->assertEquals($email->id, $attachment->inbound_email_id);
        $this->assertEquals('test.pdf', $attachment->filename);
        $this->assertEquals('application/pdf', $attachment->content_type);
        $this->assertEquals(1024, $attachment->size);
        $this->assertEquals('inbound-emails/attachments/2025/06/27/test.pdf', $attachment->file_path);
        $this->assertEquals('test.pdf', $attachment->original_name);
        $this->assertEquals('attachment', $attachment->disposition);
    }

    /** @test */
    public function it_casts_size_as_integer()
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
            'size' => '1024', // String
            'file_path' => 'path/to/file.pdf',
            'original_name' => 'test.pdf',
            'disposition' => 'attachment',
        ]);

        $this->assertIsInt($attachment->size);
        $this->assertEquals(1024, $attachment->size);
    }

    /** @test */
    public function it_has_email_relationship()
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

        $this->assertInstanceOf(InboundEmail::class, $attachment->email);
        $this->assertEquals($email->id, $attachment->email->id);
    }

    /** @test */
    public function it_generates_url_attribute()
    {
        Storage::fake('local');

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
            'file_path' => 'inbound-emails/attachments/test.pdf',
            'original_name' => 'test.pdf',
            'disposition' => 'attachment',
        ]);

        $this->assertStringContainsString('inbound-emails/attachments/test.pdf', $attachment->url);
    }

    /** @test */
    public function it_formats_size_correctly()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        // Test bytes
        $attachment = InboundEmailAttachment::create([
            'inbound_email_id' => $email->id,
            'filename' => 'test.txt',
            'content_type' => 'text/plain',
            'size' => 512,
            'file_path' => 'path/to/file.txt',
            'original_name' => 'test.txt',
            'disposition' => 'attachment',
        ]);

        $this->assertEquals('512 B', $attachment->formatted_size);

        // Test KB
        $attachment->size = 1024;
        $this->assertEquals('1 KB', $attachment->formatted_size);

        // Test MB
        $attachment->size = 1024 * 1024;
        $this->assertEquals('1 MB', $attachment->formatted_size);

        // Test GB
        $attachment->size = 1024 * 1024 * 1024;
        $this->assertEquals('1 GB', $attachment->formatted_size);

        // Test TB
        $attachment->size = 1024 * 1024 * 1024 * 1024;
        $this->assertEquals('1 TB', $attachment->formatted_size);
    }

    /** @test */
    public function it_formats_decimal_sizes_correctly()
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
            'size' => 1536, // 1.5 KB
            'file_path' => 'path/to/file.pdf',
            'original_name' => 'test.pdf',
            'disposition' => 'attachment',
        ]);

        $this->assertEquals('1.5 KB', $attachment->formatted_size);
    }

    /** @test */
    public function it_filters_by_size()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        InboundEmailAttachment::create([
            'inbound_email_id' => $email->id,
            'filename' => 'small.pdf',
            'content_type' => 'application/pdf',
            'size' => 512,
            'file_path' => 'path/to/small.pdf',
            'original_name' => 'small.pdf',
            'disposition' => 'attachment',
        ]);

        InboundEmailAttachment::create([
            'inbound_email_id' => $email->id,
            'filename' => 'large.pdf',
            'content_type' => 'application/pdf',
            'size' => 2048,
            'file_path' => 'path/to/large.pdf',
            'original_name' => 'large.pdf',
            'disposition' => 'attachment',
        ]);

        $smallAttachments = InboundEmailAttachment::bySize(1024)->get();
        $this->assertEquals(1, $smallAttachments->count());
        $this->assertEquals('small.pdf', $smallAttachments->first()->filename);
    }

    /** @test */
    public function it_filters_by_content_type()
    {
        $email = InboundEmail::create([
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        InboundEmailAttachment::create([
            'inbound_email_id' => $email->id,
            'filename' => 'document.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024,
            'file_path' => 'path/to/document.pdf',
            'original_name' => 'document.pdf',
            'disposition' => 'attachment',
        ]);

        InboundEmailAttachment::create([
            'inbound_email_id' => $email->id,
            'filename' => 'image.jpg',
            'content_type' => 'image/jpeg',
            'size' => 1024,
            'file_path' => 'path/to/image.jpg',
            'original_name' => 'image.jpg',
            'disposition' => 'attachment',
        ]);

        $pdfAttachments = InboundEmailAttachment::byContentType('application/pdf')->get();
        $this->assertEquals(1, $pdfAttachments->count());
        $this->assertEquals('document.pdf', $pdfAttachments->first()->filename);
    }

    /** @test */
    public function it_handles_inline_disposition()
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
            'filename' => 'image.jpg',
            'content_type' => 'image/jpeg',
            'size' => 1024,
            'file_path' => 'path/to/image.jpg',
            'original_name' => 'image.jpg',
            'disposition' => 'inline',
        ]);

        $this->assertEquals('inline', $attachment->disposition);
    }
}
