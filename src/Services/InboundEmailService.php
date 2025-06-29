<?php

namespace Fullstack\Inbounder\Services;

use Exception;
use Fullstack\Inbounder\Events\InboundEmailFailed;
use Fullstack\Inbounder\Events\InboundEmailProcessed;
use Fullstack\Inbounder\Events\InboundEmailReceived;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InboundEmailService
{
    private $sender = null;

    private $userResolver;

    private $tenantResolver;

    public function __construct(?callable $userResolver = null, ?callable $tenantResolver = null)
    {
        $this->userResolver = $userResolver;
        $this->tenantResolver = $tenantResolver;
    }

    /**
     * Get the user model class from config.
     */
    private function getUserModelClass(): string
    {
        return config('inbounder.models.user', 'User');
    }

    /**
     * Get the tenant model class from config.
     */
    private function getTenantModelClass(): string
    {
        return config('inbounder.models.tenant', 'Tenant');
    }

    /**
     * Process an inbound email from Mailgun.
     */
    public function processInboundEmail(Request $request): InboundEmail
    {
        try {
            // Extract email data
            $emailData = $this->extractEmailData($request);

            // Authorize sender
            $this->authorizeSender($emailData['from_email']);

            // Check for duplicates
            $this->checkForDuplicates($emailData['message_id']);

            // Resolve tenant
            $tenant = $this->resolveTenantByDomain($emailData['domain'] ?? 'default');

            // Add user and tenant IDs
            $emailData['sender_id'] = $this->sender->id;
            $emailData['tenant_id'] = $tenant->id;

            // Create the email record
            $email = $this->createEmailRecord($emailData);

            // Process attachments
            $attachments = $this->extractAttachments($request);
            if (! empty($attachments)) {
                $this->processAttachments($attachments, $email->id, $request);
            }

            // Dispatch events if enabled (only on success)
            if (config('inbounder.events.enabled', true)) {
                event(new InboundEmailReceived($emailData, $attachments, $request->all()));
                event(new InboundEmailProcessed($email, $attachments));
            }

            return $email;
        } catch (Exception $e) {
            // Fallback for $emailData if not set
            $emailDataForEvent = [];
            try {
                $emailDataForEvent = isset($emailData) ? $emailData : [
                    'message_id' => $this->extractMessageId($request),
                    'from_email' => $this->extractSenderEmail($request),
                    'to_email' => $this->extractRecipientEmail($request),
                ];
            } catch (\Throwable $ex) {
                // fallback to empty array if even extraction fails
            }

            // Dispatch failure event if enabled
            if (config('inbounder.events.enabled', true)) {
                event(new InboundEmailFailed($emailDataForEvent, $e->getMessage(), $request->all()));
            }

            throw $e;
        }
    }

    /**
     * Extract email data from the request.
     */
    private function extractEmailData(Request $request): array
    {
        $to = $this->extractToEmails($request);
        $cc = $this->extractCcEmails($request);
        $bcc = $this->extractBccEmails($request);

        $emailData = [
            'message_id' => $this->extractMessageId($request),
            'from_email' => $this->extractSenderEmail($request),
            'from_name' => $this->extractSenderName($request),
            'to_email' => $this->extractRecipientEmail($request),
            'to_name' => $this->extractRecipientName($request),
            'to_emails' => $to,
            'cc_emails' => $cc,
            'bcc_emails' => $bcc,
            'subject' => $request->get('subject'),
            'body_plain' => $request->get('body-plain'),
            'body_html' => $request->get('body-html'),
            'stripped_text' => $request->get('stripped-text'),
            'stripped_html' => $request->get('stripped-html'),
            'stripped_signature' => $request->get('stripped-signature'),
            // recipient_count will be set below
            'timestamp' => $request->get('timestamp') ? \Carbon\Carbon::createFromTimestamp($request->get('timestamp')) : null,
            'token' => $request->get('token'),
            'signature' => $request->get('signature'),
            'domain' => $request->get('domain'),
            'message_headers' => json_decode($request->get('message-headers'), true),
            'envelope' => json_decode($request->get('envelope'), true),
            'attachments_count' => $request->get('attachment-count', 0),
            'size' => $request->get('message-size', 0),
        ];

        // Set recipient_count to the sum of all recipients
        $emailData['recipient_count'] = count($to) + count($cc) + count($bcc);

        // Handle signature data properly if it's an array
        if (is_array($emailData['signature'])) {
            $signatureData = $emailData['signature'];
            $emailData['token'] = $signatureData['token'] ?? $emailData['token'];
            $emailData['signature'] = $signatureData['signature'] ?? null;
            // Note: timestamp is already handled above
        }

        if (! $emailData['message_id'] || ! $emailData['from_email'] || ! $emailData['to_email']) {
            throw new Exception('Missing required email data: message_id, from_email, or to_email');
        }

        return $emailData;
    }

    /**
     * Extract attachments from the request.
     */
    private function extractAttachments(Request $request): array
    {
        $attachments = [];
        $maxFileSize = config('inbounder.attachments.max_file_size', 20 * 1024 * 1024);

        // Try to get attachments from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['attachments'])) {
            foreach ($eventData['message']['attachments'] as $index => $attachment) {
                $attachmentData = [
                    'filename' => $attachment['filename'] ?? null,
                    'content_type' => $attachment['content-type'] ?? null,
                    'size' => $attachment['size'] ?? 0,
                    'original_name' => $attachment['filename'] ?? null,
                    'disposition' => 'attachment',
                    'index' => $index + 1,
                ];

                if ($attachmentData['size'] > $maxFileSize) {
                    continue;
                }

                $attachments[] = $attachmentData;
            }
        } else {
            // Fallback to direct file upload
            $attachmentCount = $request->get('attachment-count', 0);

            for ($i = 1; $i <= $attachmentCount; $i++) {
                $file = $request->file("attachment-{$i}");
                if ($file) {
                    $attachment = [
                        'filename' => $file->getClientOriginalName(),
                        'content_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'original_name' => $file->getClientOriginalName(),
                        'disposition' => 'attachment',
                        'index' => $i,
                        '_uploaded_file' => $file,
                    ];
                } else {
                    $attachment = [
                        'filename' => $request->get("name-{$i}"),
                        'content_type' => $request->get("content-type-{$i}"),
                        'size' => $request->get("size-{$i}", 0),
                        'original_name' => $request->get("name-{$i}"),
                        'disposition' => $request->get("disposition-{$i}", 'attachment'),
                        'index' => $i,
                    ];
                }

                if ($attachment['size'] > $maxFileSize) {
                    continue;
                }

                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * Authorize the sender to send emails.
     */
    private function authorizeSender(string $senderEmail): void
    {
        $this->sender = $this->resolveUserByEmail($senderEmail);

        if (! $this->sender) {
            throw new Exception("User with email {$senderEmail} not found");
        }

        $requiredPermission = config('inbounder.authorization.required_permission', 'can-send-emails');
        $requiredRole = config('inbounder.authorization.required_role', 'tenant-admin');
        $superAdminRoles = config('inbounder.authorization.super_admin_roles', ['super-admin']);

        if (! $this->sender->hasPermissionTo($requiredPermission, $requiredRole) &&
            ! $this->sender->hasRole($superAdminRoles)) {
            throw new Exception("User {$senderEmail} is not authorized to send emails");
        }
    }

    /**
     * Check for duplicate emails.
     */
    private function checkForDuplicates(string $messageId): void
    {
        $existingEmail = InboundEmail::where('message_id', $messageId)->first();

        if ($existingEmail) {
            throw new Exception('Email with this message ID has already been processed');
        }
    }

    /**
     * Create the email record in the database.
     */
    private function createEmailRecord(array $emailData): InboundEmail
    {
        $email = InboundEmail::create($emailData);

        return $email;
    }

    /**
     * Process attachments and save them to disk.
     */
    private function processAttachments(array $attachments, int $emailId, Request $request): void
    {
        foreach ($attachments as $attachment) {
            try {
                $filePath = $this->saveAttachmentToDisk($attachment, $request);

                InboundEmailAttachment::create([
                    'inbound_email_id' => $emailId,
                    'filename' => $attachment['filename'],
                    'content_type' => $attachment['content_type'],
                    'size' => $attachment['size'],
                    'file_path' => $filePath,
                    'original_name' => $attachment['original_name'],
                    'disposition' => $attachment['disposition'],
                ]);
            } catch (Exception $e) {
                logger()->error('Failed to save attachment', [
                    'email_id' => $emailId,
                    'filename' => $attachment['filename'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Save attachment to disk.
     */
    private function saveAttachmentToDisk(array $attachment, Request $request): string
    {
        $filename = $attachment['filename'];
        $index = $attachment['index'] ?? 1;
        $storageDisk = config('inbounder.attachments.storage_disk', 'local');
        $storagePath = config('inbounder.attachments.storage_path', 'inbound-emails/attachments');

        // If we have an UploadedFile instance, use it
        if (isset($attachment['_uploaded_file']) && $attachment['_uploaded_file'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $attachment['_uploaded_file'];
            $datePath = now()->format('Y/m/d');
            $uniqueName = uniqid().'_'.$file->getClientOriginalName();
            $fullStoragePath = "{$storagePath}/{$datePath}/{$uniqueName}";
            Storage::disk($storageDisk)->putFileAs("{$storagePath}/{$datePath}", $file, $uniqueName);

            return $fullStoragePath;
        }

        // Try different ways to get attachment content from the request
        $content = $request->get("attachment-{$index}");

        if (! $content && $filename) {
            $content = $request->get("attachment-{$filename}");
        }

        if (! $content && $filename) {
            $content = $request->get($filename);
        }

        if (! $content) {
            throw new \Exception("Attachment content not found for index {$index}, filename: {$filename}");
        }

        $datePath = now()->format('Y/m/d');
        $uniqueName = uniqid().'_'.($filename ?: 'attachment');
        $fullStoragePath = "{$storagePath}/{$datePath}/{$uniqueName}";
        Storage::disk($storageDisk)->put($fullStoragePath, $content);

        return $fullStoragePath;
    }

    // Helper methods for extracting email data
    private function extractMessageId(Request $request): ?string
    {
        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['message-id'])) {
            return $eventData['message']['headers']['message-id'];
        }

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

    private function extractSenderEmail(Request $request): ?string
    {
        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['from'])) {
            return $this->parseEmailAddress($eventData['message']['headers']['from']);
        }

        $from = $request->get('from');
        if ($from) {
            return $this->parseEmailAddress($from);
        }

        $messageHeaders = $request->get('message-headers');
        if ($messageHeaders) {
            $headers = json_decode($messageHeaders, true);
            foreach ($headers as $header) {
                if (strtolower($header[0] ?? '') === 'from') {
                    return $this->parseEmailAddress($header[1] ?? '');
                }
            }
        }

        return null;
    }

    private function extractSenderName(Request $request): ?string
    {
        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['from'])) {
            $from = $eventData['message']['headers']['from'];
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $from, $matches)) {
                return trim($matches[1], '"\'');
            }
        }

        $from = $request->get('from');
        if ($from && preg_match('/^(.+?)\s*<(.+?)>$/', $from, $matches)) {
            return trim($matches[1], '"\'');
        }

        return null;
    }

    private function extractRecipientEmail(Request $request): ?string
    {
        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['to'])) {
            $toEmails = $this->parseEmailAddresses($eventData['message']['headers']['to']);
            return $toEmails[0] ?? null; // Return first email as primary recipient
        }

        $to = $request->get('To');
        if ($to) {
            return $this->parseEmailAddress($to);
        }

        return null;
    }

    private function extractRecipientName(Request $request): ?string
    {
        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['to'])) {
            $to = $eventData['message']['headers']['to'];
            // Extract name from first recipient
            $parts = preg_split('/[,;]/', $to);
            $firstPart = trim($parts[0]);
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $firstPart, $matches)) {
                return trim($matches[1], '"\'');
            }
        }

        $to = $request->get('To');
        if ($to && preg_match('/^(.+?)\s*<(.+?)>$/', $to, $matches)) {
            return trim($matches[1], '"\'');
        }

        return null;
    }

    private function parseEmailAddress(string $emailString): ?string
    {
        if (preg_match('/<(.+?)>/', $emailString, $matches)) {
            return $matches[1];
        }

        if (filter_var($emailString, FILTER_VALIDATE_EMAIL)) {
            return $emailString;
        }

        return null;
    }

    private function resolveUserByEmail(string $email)
    {
        if ($this->userResolver) {
            return call_user_func($this->userResolver, $email);
        }
        $userModelClass = $this->getUserModelClass();

        return $userModelClass::where('email', $email)->first();
    }

    private function resolveTenantByDomain(?string $domain)
    {
        if ($this->tenantResolver) {
            return call_user_func($this->tenantResolver, $domain ?? 'default');
        }

        $tenantModelClass = $this->getTenantModelClass();

        return $tenantModelClass::where('mail_domain', $domain ?? 'default')->first();
    }

    /**
     * Extract multiple "to" email addresses from the request.
     */
    public function extractToEmails(Request $request): array
    {
        $toEmails = [];

        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['to'])) {
            $toEmails = $this->parseEmailAddresses($eventData['message']['headers']['to']);
        } else {
            // Fallback to direct request data
            $to = $request->get('to');
            if ($to) {
                $toEmails = $this->parseEmailAddresses($to);
            }
        }

        return $toEmails;
    }

    /**
     * Extract multiple "cc" email addresses from the request.
     */
    private function extractCcEmails(Request $request): array
    {
        $ccEmails = [];

        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['cc'])) {
            $ccEmails = $this->parseEmailAddresses($eventData['message']['headers']['cc']);
        } else {
            // Fallback to direct request data
            $cc = $request->get('cc');
            if ($cc) {
                $ccEmails = $this->parseEmailAddresses($cc);
            }
        }

        return $ccEmails;
    }

    /**
     * Extract multiple "bcc" email addresses from the request.
     */
    private function extractBccEmails(Request $request): array
    {
        $bccEmails = [];

        // Try to get from event-data first (Mailgun webhook format)
        $eventData = $request->get('event-data');
        if ($eventData && is_array($eventData) && isset($eventData['message']['headers']['bcc'])) {
            $bccEmails = $this->parseEmailAddresses($eventData['message']['headers']['bcc']);
        } else {
            // Fallback to direct request data
            $bcc = $request->get('bcc');
            if ($bcc) {
                $bccEmails = $this->parseEmailAddresses($bcc);
            }
        }

        return $bccEmails;
    }

    /**
     * Parse multiple email addresses from a string.
     */
    private function parseEmailAddresses(string $emailString): array
    {
        $emails = [];

        // Split by common delimiters
        $parts = preg_split('/[,;]/', $emailString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Extract email from "Name <email@domain.com>" format
            if (preg_match('/<(.+?)>/', $part, $matches)) {
                $email = trim($matches[1]);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $email;
                }
            } else {
                // Direct email address
                if (filter_var($part, FILTER_VALIDATE_EMAIL)) {
                    $emails[] = $part;
                }
            }
        }

        return array_unique($emails);
    }
}
