<?php

namespace Fullstack\Inbounder\Controllers;

use Exception;
use Fullstack\Inbounder\Controllers\Helpers\VerifySignature;
use Fullstack\Inbounder\Services\InboundEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InboundMailController extends Controller
{
    use VerifySignature;

    public function __construct(
        private InboundEmailService $inboundEmailService
    ) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            // Verify Mailgun signature
            $this->verifySignature($request);

            // Process the inbound email
            $email = $this->inboundEmailService->processInboundEmail($request);

            return response()->json([
                'message' => 'Email has been successfully processed',
                'email_id' => $email->id,
            ], 200);

        } catch (Exception $e) {
            // All errors should return 406 for Mailgun compliance
            logger()->error('Inbound message processing failed', [
                'from' => $request->get('from'),
                'to' => $request->get('To'),
                'message_id' => $this->extractMessageId($request),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 406);
        }
    }

    /**
     * Extract message ID from request for logging.
     */
    private function extractMessageId(Request $request): ?string
    {
        $messageHeaders = $request->get('message-headers');
        if ($messageHeaders) {
            $headers = json_decode($messageHeaders, true);
            foreach ($headers as $header) {
                if (strtolower($header[0] ?? '') === 'message-id') {
                    return $header[1] ?? null;
                }
            }
        }

        return null;
    }
}
