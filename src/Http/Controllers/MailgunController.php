<?php

declare(strict_types=1);

namespace Inbounder\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inbounder\Services\MailgunService;

class MailgunController
{
    /**
     * Create a new controller instance.
     *
     * @param  MailgunService  $mailgunService  The Mailgun service instance.
     */
    public function __construct(
        private MailgunService $mailgunService
    ) {}

    /**
     * Handle inbound emails from Mailgun.
     *
     * @param  Request  $request  The HTTP request containing the inbound email data.
     * @return \Illuminate\Http\JsonResponse The HTTP JSON response.
     */
    public function inbound(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->mailgunService->handleInbound($request);

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Handle webhooks from Mailgun (delivery, bounces, etc.).
     *
     * @param  Request  $request  The HTTP request containing the webhook data.
     * @return \Illuminate\Http\JsonResponse The HTTP JSON response.
     */
    public function webhook(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->mailgunService->handleWebhook($request);

        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Create a new inbound email.
     *
     * @param  Request  $request  The HTTP request containing the inbound email data.
     * @return \Illuminate\Http\JsonResponse The HTTP JSON response.
     */
    public function create(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->mailgunService->createInboundEmail($request);

        return response()->json(['message' => 'success'], 200);
    }
}
