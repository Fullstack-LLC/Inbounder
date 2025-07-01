<?php

declare(strict_types=1);

namespace Inbounder\Exceptions;

use Exception;

/**
 * Exception thrown when Mailgun tracking operations fail.
 *
 * This exception is thrown when there are errors in creating,
 * updating, or retrieving outbound email tracking records.
 */
class MailgunTrackingException extends Exception
{
    /**
     * Create a new MailgunTrackingException instance.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
