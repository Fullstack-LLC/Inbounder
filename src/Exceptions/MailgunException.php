<?php

declare(strict_types=1);

namespace Inbounder\Exceptions;

use Exception;
use Throwable;

/**
 * Base exception for Mailgun-related errors.
 */
class MailgunException extends Exception
{
    /**
     * Create a new Mailgun exception instance.
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
