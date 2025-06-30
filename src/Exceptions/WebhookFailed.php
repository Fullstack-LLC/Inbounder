<?php

namespace Fullstack\Inbounder\Exceptions;

use Exception;
use Spatie\WebhookClient\Models\WebhookCall;

final class WebhookFailed extends Exception
{
    /**
     * @return static
     */
    public static function invalidSignature(): self
    {
        return new self('The signature is invalid.');
    }

    /**
     * @return static
     */
    public static function signingSecretNotSet(): self
    {
        return new self('The webhook signing secret is not set. Make sure that the `signing_secret` config key is set to the correct value.');
    }

    /**
     * @return static
     */
    public static function jobClassDoesNotExist(string $jobClass, WebhookCall $webhookCall): self
    {
        return new self("Could not process webhook id `{$webhookCall->getKey()}` of type `{$webhookCall->getAttribute('type')} because the configured jobclass `$jobClass` does not exist.");
    }

    /**
     * @return static
     */
    public static function missingType(WebhookCall $webhookCall): self
    {
        return new self("Webhook call id `{$webhookCall->getKey()}` did not contain a type. Valid Mailgun webhook calls should always contain a type.");
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function render($request)
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
