<?php

declare(strict_types=1);

namespace Inbounder\Examples;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Inbounder\Traits\CanSendEmails;

/**
 * Example User model showing how to use the CanSendEmails trait.
 *
 * This is just an example - you would add this trait to your actual User model.
 */
class UserWithEmailTrait extends Authenticatable
{
    use CanSendEmails;

    // ... your existing User model code ...

    /**
     * Example method that checks if user can send emails before proceeding.
     */
    public function sendEmailToDistributionList($distributionList, $template, $variables = [])
    {
        if (! $this->canSendEmails()) {
            throw new \Exception('User is not authorized to send emails.');
        }

        // Proceed with sending email
        // ... email sending logic ...
    }

    /**
     * Example method that uses the trait in a conditional.
     */
    public function canAccessEmailFeatures(): bool
    {
        return $this->canSendEmails();
    }
}
