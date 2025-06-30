<?php

namespace Fullstack\Inbounder;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

class MailgunWebhooksController
{
    /**
     * Invoke controller method.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, ?string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'mailgun',
            'signing_secret' => ($configKey) ?
                config('inbounder.signing_secret_'.$configKey) :
                config('inbounder.signing_secret'),
            'signature_header_name' => null,
            'signature_validator' => MailgunSignatureValidator::class,
            'webhook_profile' => ProcessEverythingWebhookProfile::class,
            'webhook_model' => config('inbounder.model'),
            'process_webhook_job' => config('inbounder.process_webhook_job'),
        ]);

        return (new WebhookProcessor($request, $webhookConfig))->process();
    }
}
