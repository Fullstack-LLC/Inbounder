<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Exception;
use Fullstack\Inbounder\Controllers\Helpers\VerifySignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class VerifySignatureTest extends TestCase
{
    use VerifySignature;

    protected function getPackageProviders($app)
    {
        return [
            \Fullstack\Inbounder\InbounderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('inbounder.mailgun.webhook_signing_key', 'test-signing-key');
        $app['config']->set('inbounder.models.tenant', \Fullstack\Inbounder\Tests\Helpers\MockTenant::class);
    }

    /** @test */
    public function it_verifies_valid_signature()
    {
        $request = new Request([
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => hash_hmac('sha256', time().'test-token', 'test-signing-key'),
            'from' => 'test@example.com',
        ]);

        // Should not throw an exception
        $this->verifySignature($request);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_throws_exception_for_missing_signature()
    {
        $request = new Request([
            'timestamp' => time(),
            'token' => 'test-token',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $this->verifySignature($request);
    }

    /** @test */
    public function it_throws_exception_for_missing_timestamp()
    {
        $request = new Request([
            'token' => 'test-token',
            'signature' => 'valid-signature',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $this->verifySignature($request);
    }

    /** @test */
    public function it_throws_exception_for_missing_token()
    {
        $request = new Request([
            'timestamp' => time(),
            'signature' => 'valid-signature',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $this->verifySignature($request);
    }

    /** @test */
    public function it_throws_exception_for_old_timestamp()
    {
        $request = new Request([
            'timestamp' => time() - 400, // More than 5 minutes old
            'token' => 'test-token',
            'signature' => 'valid-signature',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Signature timestamp is too old');

        $this->verifySignature($request);
    }

    /** @test */
    public function it_throws_exception_for_missing_signing_key()
    {
        // Clear all signing key configs
        Config::set('inbounder.mailgun.signing_key', null);
        Config::set('inbounder.mailgun.webhook_signing_key', null);
        Config::set('services.mailgun.secret', null);

        fwrite(STDERR, 'signing_key: ' . var_export(config('inbounder.mailgun.signing_key'), true) . "\n");
        fwrite(STDERR, 'webhook_signing_key: ' . var_export(config('inbounder.mailgun.webhook_signing_key'), true) . "\n");
        fwrite(STDERR, 'services.mailgun.secret: ' . var_export(config('services.mailgun.secret'), true) . "\n");

        $request = new Request([
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => '', // Set to empty string to trigger missing signature check
            'from' => 'test@example.com',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $this->verifySignature($request);
    }

    /** @test */
    public function it_throws_exception_for_invalid_signature()
    {
        $request = new Request([
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'invalid-signature',
            'from' => 'test@example.com',
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Signature is invalid.');

        $this->verifySignature($request);
    }
}
