<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Exception;
use Fullstack\Inbounder\Tests\Helpers\MockTenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class VerifySignatureTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Fullstack\Inbounder\InbounderServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('inbounder.mailgun.signing_key', 'test-signing-key');
        $app['config']->set('inbounder.models.tenant', MockTenant::class);
    }

    /** @test */
    public function it_verifies_valid_signature()
    {
        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();

        // Should not throw an exception
        $trait->test_verify_signature($request);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    /** @test */
    public function it_throws_exception_for_missing_signature()
    {
        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();
        $request->offsetUnset('signature');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $trait->test_verify_signature($request);
    }

    /** @test */
    public function it_throws_exception_for_missing_timestamp()
    {
        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();
        $request->offsetUnset('timestamp');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $trait->test_verify_signature($request);
    }

    /** @test */
    public function it_throws_exception_for_missing_token()
    {
        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();
        $request->offsetUnset('signature.token');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing signature parameters');

        $trait->test_verify_signature($request);
    }

    /** @test */
    public function it_throws_exception_for_old_timestamp()
    {
        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();
        $request->offsetSet('timestamp', time() - 400); // More than 5 minutes old

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Signature timestamp is too old');

        $trait->test_verify_signature($request);
    }

    /** @test */
    public function it_throws_exception_for_missing_signing_key()
    {
        Config::set('inbounder.mailgun.signing_key', null);

        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mailgun signing key not configured');

        $trait->test_verify_signature($request);
    }

    /** @test */
    public function it_throws_exception_for_invalid_signature()
    {
        $trait = $this->getTraitInstance();
        $request = $this->createValidRequest();
        $request->offsetSet('signature', 'invalid-signature');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Signature is invalid.');

        $trait->test_verify_signature($request);
    }

    private function getTraitInstance()
    {
        return new class
        {
            use \Fullstack\Inbounder\Controllers\Helpers\VerifySignature;

            public function test_verify_signature($request)
            {
                return $this->verifySignature($request);
            }

            private function extractDomain($email)
            {
                return explode('@', $email)[1] ?? 'example.com';
            }
        };
    }

    private function createValidRequest(): Request
    {
        $timestamp = time();
        $token = 'test-token';
        $signingKey = 'test-signing-key';
        $signature = hash_hmac('sha256', $timestamp.$token, $signingKey);

        $request = new Request;
        $request->offsetSet('signature', $signature);
        $request->offsetSet('timestamp', $timestamp);
        $request->offsetSet('signature.token', $token);
        $request->offsetSet('from', 'test@example.com');

        return $request;
    }
}
