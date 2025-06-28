<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Exception;
use Fullstack\Inbounder\Events\InboundEmailFailed;
use Fullstack\Inbounder\Events\InboundEmailProcessed;
use Fullstack\Inbounder\Events\InboundEmailReceived;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Fullstack\Inbounder\Services\InboundEmailService;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Fullstack\Inbounder\Tests\Helpers\MockUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class InboundEmailServiceTest extends TestCase
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
        $app['config']->set('inbounder.attachments.max_file_size', 20 * 1024 * 1024);
        $app['config']->set('inbounder.attachments.storage_disk', 'local');
        $app['config']->set('inbounder.attachments.storage_path', 'inbound-emails/attachments');
        $app['config']->set('inbounder.events.dispatch_events', true);
    }

    /** @test */
    public function it_processes_inbound_email_successfully()
    {
        Event::fake();
        Storage::fake('local');

        $request = $this->createValidRequest();
        $service = $this->getServiceWithMocks();

        $email = $service->processInboundEmail($request);

        $this->assertInstanceOf(InboundEmail::class, $email);
        $this->assertEquals('<test@example.com>', $email->message_id);
        $this->assertEquals('sender@example.com', $email->from_email);
        $this->assertEquals('recipient@example.com', $email->to_email);

        Event::assertDispatched(InboundEmailReceived::class);
        Event::assertDispatched(InboundEmailProcessed::class);
    }

    /** @test */
    public function it_throws_exception_for_missing_required_data()
    {
        $request = $this->createValidRequest();
        $request->offsetUnset('message-headers');
        $request->offsetUnset('from');
        $request->offsetUnset('To');

        $service = $this->getServiceWithMocks();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing required email data: message_id, from_email, or to_email');

        $service->processInboundEmail($request);
    }

    /** @test */
    public function it_throws_exception_for_duplicate_message_id()
    {
        $request = $this->createValidRequest();
        $service = $this->getServiceWithMocks();

        // First, create an email with the same message ID
        $service->processInboundEmail($request);

        // Now try to process another email with the same message ID
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Email with this message ID has already been processed');

        $service->processInboundEmail($request);
    }

    /** @test */
    public function it_extracts_attachments_from_event_data()
    {
        Event::fake();
        Storage::fake('local');

        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'attachments' => [
                        [
                            'filename' => 'test.pdf',
                            'content-type' => 'application/pdf',
                            'size' => 1024,
                        ],
                    ],
                ],
            ],
            'attachment-1' => 'PDF content here',
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertDatabaseHas('inbound_email_attachments', [
            'inbound_email_id' => $email->id,
            'filename' => 'test.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024,
        ]);
    }

    /** @test */
    public function it_skips_attachments_exceeding_max_size()
    {
        Event::fake();
        Storage::fake('local');

        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'attachments' => [
                        [
                            'filename' => 'large.pdf',
                            'content-type' => 'application/pdf',
                            'size' => 25 * 1024 * 1024, // 25MB, exceeds 20MB limit
                        ],
                    ],
                ],
            ],
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertDatabaseMissing('inbound_email_attachments', [
            'filename' => 'large.pdf',
        ]);
    }

    /** @test */
    public function it_processes_uploaded_files()
    {
        Event::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.pdf', 1024);

        $request = $this->createValidRequest();
        $request->offsetSet('attachment-count', 1);
        $request->files->set('attachment-1', $file);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertDatabaseHas('inbound_email_attachments', [
            'inbound_email_id' => $email->id,
            'filename' => 'test.pdf',
        ]);
    }

    /** @test */
    public function it_handles_attachment_content_from_request()
    {
        Event::fake();
        Storage::fake('local');

        $request = $this->createValidRequest();
        $request->offsetSet('attachment-count', 1);
        $request->offsetSet('name-1', 'test.pdf');
        $request->offsetSet('content-type-1', 'application/pdf');
        $request->offsetSet('size-1', 1024);
        $request->offsetSet('attachment-1', 'PDF content here');

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertDatabaseHas('inbound_email_attachments', [
            'inbound_email_id' => $email->id,
            'filename' => 'test.pdf',
            'content_type' => 'application/pdf',
            'size' => 1024,
        ]);
    }

    /** @test */
    public function it_dispatches_failure_event_on_exception()
    {
        Event::fake();

        $request = $this->createValidRequest();
        $request->offsetUnset('message-headers'); // This will cause an exception

        $service = $this->getServiceWithMocks();

        try {
            $service->processInboundEmail($request);
        } catch (Exception $e) {
            // Expected
        }

        Event::assertDispatched(InboundEmailFailed::class);
    }

    /** @test */
    public function it_does_not_dispatch_events_when_disabled()
    {
        Config::set('inbounder.events.dispatch_events', false);
        Event::fake();

        $request = $this->createValidRequest();
        $service = $this->getServiceWithMocks();

        $service->processInboundEmail($request);

        Event::assertNotDispatched(InboundEmailReceived::class);
        Event::assertNotDispatched(InboundEmailProcessed::class);
    }

    /** @test */
    public function it_extracts_sender_name_from_email()
    {
        $request = $this->createValidRequest();
        $request->offsetSet('from', 'John Doe <sender@example.com>');

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals('John Doe', $email->from_name);
    }

    /** @test */
    public function it_extracts_recipient_name_from_email()
    {
        $request = $this->createValidRequest();
        $request->offsetSet('To', 'Jane Smith <recipient@example.com>');

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals('Jane Smith', $email->to_name);
    }

    /** @test */
    public function it_handles_timestamp_conversion()
    {
        $request = $this->createValidRequest();
        $request->offsetSet('timestamp', time());

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals(time(), $email->timestamp->timestamp);
    }

    /** @test */
    public function it_handles_null_timestamp()
    {
        $request = $this->createValidRequest();
        $request->offsetUnset('timestamp');

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertNull($email->timestamp);
    }

    private function createValidRequest(): Request
    {
        $request = new Request;
        $request->offsetSet('message-headers', json_encode([
            ['Message-ID', '<test@example.com>'],
            ['From', 'sender@example.com'],
            ['To', 'recipient@example.com'],
        ]));
        $request->offsetSet('from', 'sender@example.com');
        $request->offsetSet('To', 'recipient@example.com');
        $request->offsetSet('subject', 'Test Email');
        $request->offsetSet('body-plain', 'Test body');
        $request->offsetSet('body-html', '<p>Test body</p>');
        $request->offsetSet('stripped-text', 'Stripped text');
        $request->offsetSet('stripped-html', '<p>Stripped HTML</p>');
        $request->offsetSet('stripped-signature', 'Signature');
        $request->offsetSet('recipient-count', 1);
        $request->offsetSet('token', 'test-token');
        $request->offsetSet('signature', 'test-signature');
        $request->offsetSet('domain', 'example.com');
        $request->offsetSet('envelope', json_encode(['from' => 'sender@example.com', 'to' => 'recipient@example.com']));
        $request->offsetSet('attachment-count', 0);
        $request->offsetSet('message-size', 1024);

        return $request;
    }

    private function getServiceWithMocks()
    {
        $userResolver = function ($email) {
            if ($email === 'sender@example.com') {
                $user = new \Fullstack\Inbounder\Tests\Helpers\MockUser([
                    'id' => 1,
                    'email' => 'sender@example.com',
                    'tenant_id' => 1,
                ]);
                // Ensure the properties are accessible
                $user->setAttribute('id', 1);
                $user->setAttribute('tenant_id', 1);

                return $user;
            }

            return null;
        };
        $tenantResolver = function ($domain) {
            $tenant = new \Fullstack\Inbounder\Tests\Helpers\MockTenant([
                'id' => 1,
                'mail_domain' => 'mg.example.com',
                'webhook_signing_string' => 'test-signing-key',
            ]);
            $tenant->setAttribute('id', 1);

            return $tenant;
        };

        return new \Fullstack\Inbounder\Services\InboundEmailService($userResolver, $tenantResolver);
    }
}
