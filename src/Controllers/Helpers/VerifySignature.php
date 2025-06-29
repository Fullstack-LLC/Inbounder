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
        $signatureData = $request->get('signature');

        if (is_array($signatureData)) {
            $timestamp = $signatureData['timestamp'] ?? null;
            $token = $signatureData['token'] ?? null;
            $signature = $signatureData['signature'] ?? null;
        } else {
            $timestamp = $request->get('timestamp');
            $token = $request->get('token');
            $signature = $request->get('signature');
        }

        if (! $signature || ! $timestamp || ! $token) {
            throw new Exception('Missing signature parameters');
        }

        // Check if timestamp is within 5 minutes
        $timeDiff = time() - $timestamp;
        if ($timeDiff > 300) {
            throw new Exception('Signature timestamp is too old');
        }

        // Try to get signing key from tenant-specific configuration first
        $signingKey = $this->getSigningKey($request);

        // Debug: log tenant signing key
        logger()->debug('VerifySignature tenantSigningKey', ['signingKey' => $signingKey]);

        // Fall back to global Mailgun signing key if tenant-specific key not found
        if (!$signingKey) {
            $signingKey = config('inbounder.mailgun.webhook_signing_key')
                ?? config('inbounder.mailgun.signing_key')
                ?? config('services.mailgun.secret');
        }

        // Debug: log final signing key value
        logger()->debug('VerifySignature finalSigningKey', ['signingKey' => $signingKey]);
        if (!isset($signingKey) || $signingKey === '' || $signingKey === null) {
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

    private function getSigningKey(Request $request): ?string
    {
        try {
        $fromDomain = 'mg.'.$this->extractDomain($request->get('from'));
        $tenantModelClass = $this->getTenantModelClass();

            $tenant = $tenantModelClass::where('mail_domain', $fromDomain)->first();
            return $tenant?->webhook_signing_string;
        } catch (Exception $e) {
            // If tenant lookup fails, return null to fall back to global config
            return null;
        }
    }

    private function extractDomain(string $email): string
    {
        return substr(strrchr($email, '@'), 1);
    }
}
