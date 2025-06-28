<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Fullstack\Inbounder\Tests\Helpers\MockUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class InboundEmailModelTest extends TestCase
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
        Config::set('inbounder.models.user', MockUser::class);
        Config::set('inbounder.models.tenant', MockTenant::class);

        $email = new InboundEmail;

        $senderRelationship = $email->sender();
        $tenantRelationship = $email->tenant();

        // The relationships should be set up with the configurable model classes
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $senderRelationship);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $tenantRelationship);
    }
}
