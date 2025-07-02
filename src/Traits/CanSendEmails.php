<?php

declare(strict_types=1);

namespace Inbounder\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

trait CanSendEmails
{
    /**
     * Get a config value with proper fallback.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    private function getConfig(string $key, $default = null)
    {
        return config($key, $default);
    }

    public function canSendEmails(): bool
    {
        $method = $this->getConfig('mailgun.authorization.method', 'gate');
        $gateName = $this->getConfig('mailgun.authorization.gate_name', 'send-email');
        $policyMethod = $this->getConfig('mailgun.authorization.policy_method', 'sendEmail');
        $spatiePermission = $this->getConfig('mailgun.authorization.spatie_permission', 'send email');

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
