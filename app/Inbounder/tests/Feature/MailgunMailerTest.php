<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Tests\TestCase;

/**
 * Test Mailgun mailer configuration.
 */
class MailgunMailerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Mailgun mailer is configured when credentials are available.
     */
    public function test_mailgun_mailer_is_configured(): void
    {
        // Check that Mailgun mailer is configured
        $mailers = config('mail.mailers');
        $this->assertArrayHasKey('mailgun', $mailers);

        $mailgunConfig = $mailers['mailgun'];
        $this->assertEquals('mailgun', $mailgunConfig['transport']);
        $this->assertNotEmpty($mailgunConfig['domain']);
        $this->assertNotEmpty($mailgunConfig['secret']);
        $this->assertEquals('api.mailgun.net', $mailgunConfig['endpoint']);
    }

    /**
     * Test that Mailgun is set as default mailer when configured.
     */
    public function test_mailgun_is_default_mailer_when_configured(): void
    {
        // If Mailgun credentials are available, it should be the default
        if (config('mailgun.domain') && config('mailgun.secret')) {
            $this->assertEquals('mailgun', config('mail.default'));
        } else {
            $this->assertNotEquals('mailgun', config('mail.default'));
        }
    }

    /**
     * Test that default from address is set from Mailgun config.
     */
    public function test_default_from_address_is_set(): void
    {
        $fromConfig = config('mail.from');
        $this->assertIsArray($fromConfig);
        $this->assertArrayHasKey('address', $fromConfig);
        $this->assertArrayHasKey('name', $fromConfig);
        $this->assertNotEmpty($fromConfig['address']);
        $this->assertNotEmpty($fromConfig['name']);
    }

    /**
     * Test that outbound configuration is available.
     */
    public function test_outbound_configuration_is_available(): void
    {
        $outboundConfig = config('mailgun.outbound');
        $this->assertIsArray($outboundConfig);
        $this->assertArrayHasKey('enabled', $outboundConfig);
        $this->assertArrayHasKey('default_from', $outboundConfig);
        $this->assertArrayHasKey('tracking', $outboundConfig);
    }

    /**
     * Test that tracking configuration is available.
     */
    public function test_tracking_configuration_is_available(): void
    {
        $trackingConfig = config('mailgun.outbound.tracking');
        $this->assertIsArray($trackingConfig);
        $this->assertArrayHasKey('opens', $trackingConfig);
        $this->assertArrayHasKey('clicks', $trackingConfig);
        $this->assertArrayHasKey('unsubscribes', $trackingConfig);
    }
}
