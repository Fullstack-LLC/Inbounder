<?php

declare(strict_types=1);

namespace Inbounder\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when webhook processing fails.
 */
class MailgunWebhookException extends MailgunException
{
    /**
     * Create a new webhook exception instance.
     *
     * @param  string  $message  The exception message.
     * @param  int  $code  The exception code.
     * @param  Throwable|null  $previous  The previous exception.
     */
    public function __construct(string $message, int $code, Throwable $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}
