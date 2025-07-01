<?php

namespace Inbounder\Tests\Feature;

use Inbounder\Tests\TestCase;

uses(TestCase::class);

describe('Mailgun Middleware Edge Cases', function () {
    beforeEach(function () {
        config(['mailgun.webhook.verify_signature' => true]);
        config(['mailgun.force_signature_testing' => true]);
        config(['services.mailgun.webhook_signing_key' => 'test-signing-key']);
    });

    it('skips verification if disabled in config', function () {
        config(['mailgun.webhook.verify_signature' => false]);
        $payload = [
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'any-signature',
        ];
        $response = $this->postJson(route('mailgun.webhook'), $payload);
        $response->assertStatus(200);
    });

    it('returns 401 if missing required parameters', function () {
        $payload = [
        ];
        $response = $this->postJson(route('mailgun.webhook'), $payload);
        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid webhook signature']);
    });

    it('returns 401 if timestamp is too old', function () {
        $oldTimestamp = time() - 10000;
        $payload = [
            'timestamp' => $oldTimestamp,
            'token' => 'test-token',
            'signature' => 'any-signature',
        ];
        $response = $this->postJson(route('mailgun.webhook'), $payload);
        $response->assertStatus(401)
            ->assertJson(['error' => 'Webhook timestamp too old']);
    });

    it('returns 500 if signing key is not configured', function () {
        config(['services.mailgun.webhook_signing_key' => null]);
        $payload = [
            'timestamp' => time(),
            'token' => 'test-token',
            'signature' => 'any-signature',
        ];
        $response = $this->postJson(route('mailgun.webhook'), $payload);
        $response->assertStatus(500)
            ->assertJson(['error' => 'Webhook signing key not configured']);
    });

    it('allows request with valid signature', function () {
        $timestamp = time();
        $token = 'test-token';
        $apiKey = 'test-signing-key';
        $signature = hash_hmac('sha256', $timestamp.$token, $apiKey);
        $payload = [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $signature,
        ];
        $response = $this->postJson(route('mailgun.webhook'), $payload);
        $response->assertStatus(200);
    });
});
