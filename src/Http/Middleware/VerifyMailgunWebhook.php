<?php

declare(strict_types=1);

namespace Inbounder\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware to verify Mailgun webhook signatures.
 *
 * This middleware validates the signature, timestamp, and token provided by Mailgun
 * to ensure the webhook request is authentic and not tampered with.
 */
class VerifyMailgunWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Closure  $next  The next middleware in the stack.
     * @return Response|JsonResponse The HTTP response.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $this->getConfig('mailgun.webhook.verify_signature', true)) {
            return $next($request);
        }

        if ($this->getConfig('mailgun.force_signature_testing', false)) {
            return $this->verifySignature($request, $next);
        }

        if ($request->is('api/mail/webhooks/*') || $request->is('api/mail/webhooks')) {
            return $this->verifySignature($request, $next);
        }

        return $next($request);
    }

    /**
     * Verify the Mailgun webhook signature.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Closure  $next  The next middleware in the stack.
     * @return Response|JsonResponse The response from the next middleware or an error response.
     */
    private function verifySignature(Request $request, Closure $next): Response|JsonResponse
    {
        $timestamp = $request->input('timestamp') ?? $request->input('signature.timestamp');
        $token = $request->input('token') ?? $request->input('signature.token');

        $signatureInput = $request->input('signature');
        if (is_array($signatureInput)) {
            $signature = $signatureInput['signature'] ?? null;
        } else {
            $signature = $signatureInput;
        }

        if (! $timestamp || ! $token || ! $signature) {
            logger()->error('Mailgun webhook missing required parameters');
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        $tolerance = $this->getConfig('mailgun.webhook.timestamp_tolerance', 300);
        if (time() - $timestamp > $tolerance) {
            logger()->error('Mailgun webhook timestamp too old');
            return response()->json(['error' => 'Webhook timestamp too old'], 401);
        }

        $signingKey = $this->getConfig('mailgun.webhook_signing_key');
        if (! $signingKey) {
            logger()->error('Mailgun webhook signing key not configured');
            return response()->json(['error' => 'Webhook signing key not configured'], 500);
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.$token, $signingKey);
        if (! hash_equals($expectedSignature, $signature)) {
            logger()->error('Mailgun webhook signature verification failed');
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        return $next($request);
    }

    /**
     * Get a config value with proper fallback.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getConfig(string $key, $default = null)
    {
        return config($key, $default);
    }
}
