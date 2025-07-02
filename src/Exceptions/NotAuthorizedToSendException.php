<?php

declare(strict_types=1);

namespace Inbounder\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when sender is not authorized to send emails.
 */
class NotAuthorizedToSendException extends Exception
{
    /**
     * Create a new NotAuthorizedToSendException instance.
     */
    public function __construct()
    {
        parent::__construct('Sender is not authorized to send emails to this system', 403);
    }
}
