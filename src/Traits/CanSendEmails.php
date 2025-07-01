<?php

declare(strict_types=1);

namespace Inbounder\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

trait CanSendEmails
{
    public function canSendEmails(): bool
    {
        $method = Config::get('mailgun.authorization.method', 'gate');
        $gateName = Config::get('mailgun.authorization.gate_name', 'send-email');
        $policyMethod = Config::get('mailgun.authorization.policy_method', 'sendEmail');
        $spatiePermission = Config::get('mailgun.authorization.spatie_permission', 'send email');

        switch ($method) {
            case 'none':
                return true;
            case 'spatie':
                return method_exists($this, 'hasPermissionTo')
                    ? $this->hasPermissionTo($spatiePermission)
                    : false;
            case 'policy':
                return method_exists($this, 'can')
                    ? $this->can($policyMethod)
                    : false;
            case 'gate':
            default:
                return Gate::allows($gateName, $this);
        }
    }
}
