<?php

declare(strict_types=1);

namespace Inbounder\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model for storing Mailgun inbound emails.
 *
 * This model represents inbound emails received through Mailgun's
 * inbound processing feature.
 */
class MailgunInboundEmail extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mailgun_inbound_emails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'from',
        'to',
        'subject',
        'body_plain',
        'body_html',
        'message_id',
        'timestamp',
        'token',
        'signature',
        'recipient',
        'sender',
        'stripped_text',
        'stripped_html',
        'stripped_signature',
        'message_headers',
        'content_id_map',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'message_headers' => 'array',
        'content_id_map' => 'array',
        'raw_data' => 'array',
        'timestamp' => 'datetime',
    ];

    /**
     * Get the sender email address.
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Get the recipient email address.
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * Get the email subject.
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Get the plain text body.
     */
    public function getBodyPlain(): ?string
    {
        return $this->body_plain;
    }

    /**
     * Get the HTML body.
     */
    public function getBodyHtml(): ?string
    {
        return $this->body_html;
    }

    /**
     * Get the message ID.
     */
    public function getMessageId(): ?string
    {
        return $this->message_id;
    }

    /**
     * Get the timestamp.
     */
    public function getTimestamp(): ?\Carbon\Carbon
    {
        return $this->timestamp;
    }

    /**
     * Get the token.
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Get the signature.
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * Get the recipient.
     */
    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    /**
     * Get the sender.
     */
    public function getSender(): ?string
    {
        return $this->sender;
    }

    /**
     * Get the stripped text.
     */
    public function getStrippedText(): ?string
    {
        return $this->stripped_text;
    }

    /**
     * Get the stripped HTML.
     */
    public function getStrippedHtml(): ?string
    {
        return $this->stripped_html;
    }

    /**
     * Get the stripped signature.
     */
    public function getStrippedSignature(): ?string
    {
        return $this->stripped_signature;
    }

    /**
     * Get the message headers.
     */
    public function getMessageHeaders(): ?array
    {
        return $this->message_headers;
    }

    /**
     * Get the content ID map.
     */
    public function getContentIdMap(): ?array
    {
        return $this->content_id_map;
    }

    /**
     * Get the raw data.
     */
    public function getRawData(): ?array
    {
        return $this->raw_data;
    }
}
