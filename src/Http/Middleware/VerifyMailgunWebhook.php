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
        if (! config('mailgun.webhook.verify_signature', true)) {
            return $next($request);
        }

        if (config('mailgun.force_signature_testing', false)) {
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
        // Try to get signature parameters from both flat and nested structures
        $timestamp = $request->input('timestamp') ?? $request->input('signature.timestamp');
        $token = $request->input('token') ?? $request->input('signature.token');
        $signature = $request->input('signature') ?? $request->input('signature.signature');

        if (! $timestamp || ! $token || ! $signature) {
            Log::warning('Mailgun webhook missing required parameters', [
                'timestamp' => $timestamp,
                'token' => $token,
                'signature' => $signature,
                'request_data' => $request->all(),
            ]);

            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        $tolerance = config('mailgun.webhook.timestamp_tolerance', 300);
        if (time() - $timestamp > $tolerance) {
            Log::warning('Mailgun webhook timestamp too old', [
                'timestamp' => $timestamp,
                'current_time' => time(),
                'tolerance' => $tolerance,
            ]);

            return response()->json(['error' => 'Webhook timestamp too old'], 401);
        }

        $signingKey = config('mailgun.webhook_signing_key');
        if (! $signingKey) {
            Log::error('Mailgun webhook signing key not configured');

            return response()->json(['error' => 'Webhook signing key not configured'], 500);
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.$token, $signingKey);
        if (! hash_equals($expectedSignature, $signature)) {
            Log::warning('Mailgun webhook signature verification failed', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);

            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        Log::info('Mailgun webhook signature verified successfully');

        return $next($request);
    }
}
