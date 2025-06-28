<?php

namespace Fullstack\Inbounder\Controllers\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait VerifySignature
{
    /**
     * Get the tenant model class from config.
     */
    private function getTenantModelClass(): string
    {
        return config('inbounder.models.tenant', \App\Models\Tenant::class);
    }

    /**
     * Verify the Mailgun webhook signature.
     *
     * @throws Exception if signature is invalid
     */
    protected function verifySignature(Request $request): void
    {
        $signature = $request->get('signature');
        $timestamp = $request->get('timestamp');
        $token = $request->get('signature.token');

        if (! $signature || ! $timestamp || ! $token) {
            throw new Exception('Missing signature parameters');
        }

        // Check if timestamp is within 5 minutes
        $timeDiff = time() - $timestamp;
        if ($timeDiff > 300) {
            throw new Exception('Signature timestamp is too old');
        }

        // Verify signature
        $signingKey = config('inbounder.mailgun.signing_key');
        if (! $signingKey) {
            throw new Exception('Mailgun signing key not configured');
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.$token, $signingKey);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new Exception('Signature is invalid.');
        }

        logger()->info('Signature verified', [
            'timestamp' => $timestamp,
            'timestamp_diff' => $timeDiff.' seconds',
        ]);
    }

    private function getSigningKey(Request $request): string
    {
        $fromDomain = 'mg.'.$this->extractDomain($request->get('from'));
        $tenantModelClass = $this->getTenantModelClass();

        $signingKey = $tenantModelClass::where('mail_domain', $fromDomain)->first()->webhook_signing_string ?? null;
        if (! $signingKey) {
            throw new Exception('No signing key found for domain '.$fromDomain.'.');
        }

        return $signingKey;
    }
}
