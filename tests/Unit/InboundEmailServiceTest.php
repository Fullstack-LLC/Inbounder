<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Exception;
use Fullstack\Inbounder\Events\InboundEmailFailed;
use Fullstack\Inbounder\Events\InboundEmailProcessed;
use Fullstack\Inbounder\Events\InboundEmailReceived;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Fullstack\Inbounder\Services\InboundEmailService;
use Fullstack\Inbounder\Tests\Helpers\MockUser;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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

    /** @test */
    public function it_extracts_multiple_to_emails()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'headers' => [
                        'to' => 'John Doe <john@example.com>, Jane Smith <jane@example.com>'
                    ]
                ]
            ]
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals(['john@example.com', 'jane@example.com'], $email->to_emails);
    }

    /** @test */
    public function it_extracts_multiple_cc_emails()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'headers' => [
                        'cc' => 'cc1@example.com, cc2@example.com'
                    ]
                ]
            ]
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals(['cc1@example.com', 'cc2@example.com'], $email->cc_emails);
    }

    /** @test */
    public function it_extracts_multiple_bcc_emails()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'headers' => [
                        'bcc' => 'bcc@example.com'
                    ]
                ]
            ]
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals(['bcc@example.com'], $email->bcc_emails);
    }

    /** @test */
    public function it_parses_email_addresses_with_names()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'headers' => [
                        'to' => 'John Doe <john@example.com>, jane@example.com, "Jane Smith" <jane2@example.com>'
                    ]
                ]
            ]
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals(['john@example.com', 'jane@example.com', 'jane2@example.com'], $email->to_emails);
    }

    /** @test */
    public function it_handles_empty_recipient_fields()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'headers' => [
                        'to' => '',
                        'cc' => null,
                        'bcc' => '   '
                    ]
                ]
            ]
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals([], $email->to_emails);
        $this->assertEquals([], $email->cc_emails);
        $this->assertEquals([], $email->bcc_emails);
    }

    /** @test */
    public function it_falls_back_to_direct_request_data_for_recipients()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'to' => 'direct1@example.com, direct2@example.com',
            'cc' => 'cc@example.com',
            'bcc' => 'bcc@example.com',
            'event-data' => null
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        $this->assertEquals(['direct1@example.com', 'direct2@example.com'], $email->to_emails);
        $this->assertEquals(['cc@example.com'], $email->cc_emails);
        $this->assertEquals(['bcc@example.com'], $email->bcc_emails);
    }

    /** @test */
    public function it_saves_multiple_recipients_to_database()
    {
        $request = $this->createValidRequest();
        $request->merge([
            'event-data' => [
                'message' => [
                    'headers' => [
                        'to' => 'John Doe <john@example.com>, Jane Smith <jane@example.com>, Bob Wilson <bob@example.com>',
                        'cc' => 'cc1@example.com, cc2@example.com',
                        'bcc' => 'bcc@example.com'
                    ]
                ]
            ]
        ]);

        $service = $this->getServiceWithMocks();
        $email = $service->processInboundEmail($request);

        // Verify the email was saved to database
        $this->assertDatabaseHas('inbound_emails', [
            'id' => $email->id,
            'message_id' => '<test@example.com>',
        ]);

        // Refresh from database to ensure we're getting the actual saved data
        $savedEmail = InboundEmail::find($email->id);

        // Check that all recipients are properly saved
        $this->assertEquals([
            'john@example.com',
            'jane@example.com',
            'bob@example.com'
        ], $savedEmail->to_emails);

        $this->assertEquals([
            'cc1@example.com',
            'cc2@example.com'
        ], $savedEmail->cc_emails);

        $this->assertEquals([
            'bcc@example.com'
        ], $savedEmail->bcc_emails);

        // Verify helper methods work correctly
        $this->assertEquals(6, $savedEmail->getTotalRecipientCount());
        $this->assertTrue($savedEmail->isRecipient('john@example.com'));
        $this->assertTrue($savedEmail->isRecipient('jane@example.com'));
        $this->assertTrue($savedEmail->isRecipient('bob@example.com'));
        $this->assertTrue($savedEmail->isRecipient('cc1@example.com'));
        $this->assertTrue($savedEmail->isRecipient('cc2@example.com'));
        $this->assertTrue($savedEmail->isRecipient('bcc@example.com'));
        $this->assertFalse($savedEmail->isRecipient('not-a-recipient@example.com'));

        // Verify primary recipient
        $this->assertEquals('john@example.com', $savedEmail->getPrimaryRecipient());
    }

    public function test_can_process_real_world_mailgun_webhook_with_quoted_emails()
    {
        // Force MySQL connection
        DB::setDefaultConnection('mysql');

        // This test simulates the actual Mailgun webhook format from the log
        $request = new Request([
            'event-data' => [
                'event' => 'accepted',
                'id' => 'R942CLXPR6C3LiEtGiqQow',
                'timestamp' => 1751083231.7328398,
                'envelope' => [
                    'sender' => 'David.church@fullstackllc.net',
                    'targets' => 'test2@mg.fullstackllc.net',
                    'transport' => 'smtp'
                ],
                'flags' => [
                    'is-authenticated' => false,
                    'is-test-mode' => false
                ],
                'message' => [
                    'headers' => [
                        'message-id' => '5028114A-1A97-479D-89C5-566C63F5386F@fullstackllc.net-' . uniqid(),
                        'from' => 'David Church <David.church@fullstackllc.net>',
                        'to' => '"test@mg.fullstackllc.net" <test@mg.fullstackllc.net>, "test2@mg.fullstackllc.net" <test2@mg.fullstackllc.net>',
                        'subject' => '2'
                    ],
                    'size' => 8085
                ],
                'storage' => [
                    'key' => 'BAABAQUggNdR-GjIAjJLiKrUVndhPrg7Yw',
                    'url' => 'https://storage-us-west1.api.mailgun.net/v3/domains/mg.fullstackllc.net/messages/BAABAQUggNdR-GjIAjJLiKrUVndhPrg7Yw'
                ],
                'method' => 'SMTP',
                'log-level' => 'info',
                'recipient' => 'test2@mg.fullstackllc.net',
                'recipient-domain' => 'mg.fullstackllc.net',
                'tags' => [],
                'user-variables' => []
            ],
            'signature' => [
                'token' => 'e9293510122f44168e2e9449092dc04ea2813055e979add0f9',
                'timestamp' => '1751083231',
                'signature' => '18bc168fb9cd25c5afa1f0b5a83e2be9adbc6df8e2274d9bfe8b25ccfc0abc4d'
            ]
        ]);

        $service = new InboundEmailService(
            fn($email) => MockUser::create(['email' => $email, 'id' => 1, 'tenant_id' => 1]),
            fn($domain) => MockTenant::create(['mail_domain' => $domain, 'id' => 1])
        );

        // Process the email
        $email = $service->processInboundEmail($request);

        // Check that both emails were saved
        $this->assertCount(2, $email->to_emails);
        $this->assertContains('test@mg.fullstackllc.net', $email->to_emails);
        $this->assertContains('test2@mg.fullstackllc.net', $email->to_emails);

        // Check that the primary recipient is set correctly
        $this->assertEquals('test@mg.fullstackllc.net', $email->to_email);
    }

    public function test_can_create_inbound_email_with_json_strings_directly()
    {
        // Test direct creation with JSON strings
        $email = InboundEmail::create([
            'message_id' => 'test-123',
            'from_email' => 'test@example.com',
            'to_email' => 'recipient@example.com',
            'to_emails' => json_encode(['test@mg.fullstackllc.net', 'test2@mg.fullstackllc.net']),
            'cc_emails' => json_encode([]),
            'bcc_emails' => json_encode([]),
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertNotNull($email->id);
        $this->assertEquals('test-123', $email->message_id);
    }

    public function test_can_process_simple_request_with_multiple_recipients()
    {
        // Simple test with basic request data
        $request = new Request([
            'message-headers' => json_encode([
                ['message-id', 'test-123'],
                ['from', 'sender@example.com'],
                ['to', 'recipient1@example.com, recipient2@example.com'],
            ]),
            'from' => 'sender@example.com',
            'to' => 'recipient1@example.com, recipient2@example.com',
            'subject' => 'Test Subject',
            'body-plain' => 'Test body',
            'domain' => 'example.com',
            'token' => 'test-token',
            'signature' => 'test-signature',
        ]);

        $service = new InboundEmailService(
            fn($email) => MockUser::create(['email' => $email, 'id' => 1, 'tenant_id' => 1]),
            fn($domain) => MockTenant::create(['mail_domain' => $domain, 'id' => 1])
        );

        // Process the email
        $email = $service->processInboundEmail($request);

        // Check that both emails were saved
        $this->assertCount(2, $email->to_emails);
        $this->assertContains('recipient1@example.com', $email->to_emails);
        $this->assertContains('recipient2@example.com', $email->to_emails);

        // Check that the primary recipient is set correctly
        $this->assertEquals('recipient1@example.com', $email->to_email);
    }

    public function test_direct_model_creation_with_arrays()
    {
        // Force MySQL connection
        DB::setDefaultConnection('mysql');

        // Try to create a model directly with arrays
        $email = InboundEmail::create([
            'message_id' => 'test-direct-123',
            'from_email' => 'test@example.com',
            'to_email' => 'recipient@example.com',
            'to_emails' => ['test1@example.com', 'test2@example.com'],
            'cc_emails' => ['cc@example.com'],
            'bcc_emails' => ['bcc@example.com'],
            'subject' => 'Test',
            'sender_id' => 1,
            'tenant_id' => 1,
        ]);

        $this->assertNotNull($email->id);
        $this->assertEquals(['test1@example.com', 'test2@example.com'], $email->to_emails);
        $this->assertEquals(['cc@example.com'], $email->cc_emails);
        $this->assertEquals(['bcc@example.com'], $email->bcc_emails);
    }

    public function test_can_process_mailgun_webhook_with_multiple_recipients_all_types()
    {
        // Force MySQL connection
        DB::setDefaultConnection('mysql');

        // This test simulates a Mailgun webhook with multiple recipients in all fields
        $request = new Request([
            'event-data' => [
                'event' => 'accepted',
                'id' => 'R942CLXPR6C3LiEtGiqQow',
                'timestamp' => 1751083231.7328398,
                'envelope' => [
                    'sender' => 'sender@example.com',
                    'targets' => 'recipient1@example.com',
                    'transport' => 'smtp'
                ],
                'flags' => [
                    'is-authenticated' => false,
                    'is-test-mode' => false
                ],
                'message' => [
                    'headers' => [
                        'message-id' => 'test-multiple-recipients-' . uniqid(),
                        'from' => 'Sender Name <sender@example.com>',
                        'to' => '"Recipient 1" <recipient1@example.com>, "Recipient 2" <recipient2@example.com>',
                        'cc' => '"CC Recipient 1" <cc1@example.com>, "CC Recipient 2" <cc2@example.com>',
                        'bcc' => '"BCC Recipient 1" <bcc1@example.com>, "BCC Recipient 2" <bcc2@example.com>',
                        'subject' => 'Test with multiple recipients'
                    ],
                    'size' => 8085
                ],
                'storage' => [
                    'key' => 'test-key',
                    'url' => 'https://storage.example.com/test-key'
                ],
                'method' => 'SMTP',
                'log-level' => 'info',
                'recipient' => 'recipient1@example.com',
                'recipient-domain' => 'example.com',
                'tags' => [],
                'user-variables' => []
            ],
            'signature' => [
                'token' => 'test-token',
                'timestamp' => '1751083231',
                'signature' => 'test-signature'
            ]
        ]);

        $service = new InboundEmailService(
            fn($email) => MockUser::create(['email' => $email, 'id' => 1, 'tenant_id' => 1]),
            fn($domain) => MockTenant::create(['mail_domain' => $domain, 'id' => 1])
        );

        // Process the email
        $email = $service->processInboundEmail($request);

        // Check that all recipients were saved correctly
        $this->assertCount(2, $email->to_emails);
        $this->assertContains('recipient1@example.com', $email->to_emails);
        $this->assertContains('recipient2@example.com', $email->to_emails);

        $this->assertCount(2, $email->cc_emails);
        $this->assertContains('cc1@example.com', $email->cc_emails);
        $this->assertContains('cc2@example.com', $email->cc_emails);

        $this->assertCount(2, $email->bcc_emails);
        $this->assertContains('bcc1@example.com', $email->bcc_emails);
        $this->assertContains('bcc2@example.com', $email->bcc_emails);

        // Check that the primary recipient is set correctly (first to email)
        $this->assertEquals('recipient1@example.com', $email->to_email);

        // Check helper methods
        $actualCount = $email->getTotalRecipientCount();
        $this->assertEquals(6, $actualCount, "Expected 6 recipients but got {$actualCount}");

        $this->assertTrue($email->isRecipient('recipient1@example.com'));
        $this->assertTrue($email->isRecipient('recipient2@example.com'));
        $this->assertTrue($email->isRecipient('cc1@example.com'));
        $this->assertTrue($email->isRecipient('cc2@example.com'));
        $this->assertTrue($email->isRecipient('bcc1@example.com'));
        $this->assertTrue($email->isRecipient('bcc2@example.com'));
        $this->assertFalse($email->isRecipient('not-a-recipient@example.com'));

        // Check that all recipients are in getAllRecipients
        $allRecipients = $email->getAllRecipients();
        $this->assertCount(6, $allRecipients);
        $this->assertContains('recipient1@example.com', $allRecipients);
        $this->assertContains('recipient2@example.com', $allRecipients);
        $this->assertContains('cc1@example.com', $allRecipients);
        $this->assertContains('cc2@example.com', $allRecipients);
        $this->assertContains('bcc1@example.com', $allRecipients);
        $this->assertContains('bcc2@example.com', $allRecipients);
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
