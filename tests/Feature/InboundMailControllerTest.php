<?php

namespace Fullstack\Inbounder\Tests\Feature;

use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Services\InboundEmailService;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Fullstack\Inbounder\Tests\Helpers\MockUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\TestCase;

class InboundMailControllerTest extends TestCase
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

        // Setup Inbounder config
        $app['config']->set('inbounder.mailgun.signing_key', 'test-signing-key');
        $app['config']->set('inbounder.attachments.max_file_size', 20 * 1024 * 1024);
        $app['config']->set('inbounder.models.user', MockUser::class);
        $app['config']->set('inbounder.models.tenant', MockTenant::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->app->bind(\Fullstack\Inbounder\Services\InboundEmailService::class, function () use ($userResolver, $tenantResolver) {
            return new \Fullstack\Inbounder\Services\InboundEmailService($userResolver, $tenantResolver);
        });
    }

    /** @test */
    public function it_successfully_processes_inbound_email()
    {
        Event::fake();

        $request = $this->createValidMailgunRequest();

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email has been successfully processed',
            ])
            ->assertJsonStructure(['email_id']);

        $this->assertDatabaseHas('inbound_emails', [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'to_email' => 'recipient@example.com',
        ]);
    }

    /** @test */
    public function it_returns_406_for_invalid_signature()
    {
        $request = $this->createValidMailgunRequest();
        $request['signature'] = 'invalid-signature';

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(406)
            ->assertJson(['error' => 'Signature is invalid.']);
    }

    /** @test */
    public function it_returns_406_for_missing_signature_parameters()
    {
        $request = $this->createValidMailgunRequest();
        unset($request['signature']);

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(406)
            ->assertJson(['error' => 'Missing signature parameters']);
    }

    /** @test */
    public function it_returns_406_for_old_timestamp()
    {
        $request = $this->createValidMailgunRequest();
        $request['timestamp'] = time() - 400; // More than 5 minutes old

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(406)
            ->assertJson(['error' => 'Signature timestamp is too old']);
    }

    /** @test */
    public function it_returns_406_for_missing_signing_key()
    {
        Config::set('inbounder.mailgun.signing_key', null);

        $request = $this->createValidMailgunRequest();

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(406)
            ->assertJson(['error' => 'Mailgun signing key not configured']);
    }

    /** @test */
    public function it_returns_406_for_processing_errors()
    {
        // Mock the service to throw an exception
        $this->mock(InboundEmailService::class, function ($mock) {
            $mock->shouldReceive('processInboundEmail')
                ->once()
                ->andThrow(new \Exception('Processing failed'));
        });

        $request = $this->createValidMailgunRequest();

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(406)
            ->assertJson(['error' => 'Processing failed']);
    }

    /** @test */
    public function it_extracts_message_id_from_headers()
    {
        $request = $this->createValidMailgunRequest();
        $request['message-headers'] = json_encode([
            ['Message-ID', '<test@example.com>'],
            ['From', 'sender@example.com'],
        ]);

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_missing_message_headers()
    {
        $request = $this->createValidMailgunRequest();
        unset($request['message-headers']);

        $response = $this->postJson('/api/mail/mailgun', $request);

        $response->assertStatus(406);
    }

    private function createValidMailgunRequest(): array
    {
        $timestamp = time();
        $token = 'test-token';
        $signingKey = 'test-signing-key';
        $signature = hash_hmac('sha256', $timestamp.$token, $signingKey);

        return [
            'signature' => $signature,
            'timestamp' => $timestamp,
            'signature.token' => $token,
            'message-headers' => json_encode([
                ['Message-ID', '<test@example.com>'],
                ['From', 'sender@example.com'],
                ['To', 'recipient@example.com'],
            ]),
            'from' => 'sender@example.com',
            'To' => 'recipient@example.com',
            'subject' => 'Test Email',
            'body-plain' => 'Test body',
            'body-html' => '<p>Test body</p>',
            'stripped-text' => 'Stripped text',
            'stripped-html' => '<p>Stripped HTML</p>',
            'stripped-signature' => 'Signature',
            'recipient-count' => 1,
            'token' => $token,
            'domain' => 'example.com',
            'envelope' => json_encode(['from' => 'sender@example.com', 'to' => 'recipient@example.com']),
            'attachment-count' => 0,
            'message-size' => 1024,
        ];
    }
}
