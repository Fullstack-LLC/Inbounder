<?php

namespace Inbounder\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Inbounder\Traits\CanSendEmails;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CanSendEmailsTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('mailgun.authorization.method', 'gate');
        Config::set('mailgun.authorization.gate_name', 'send-email');
        Config::set('mailgun.authorization.policy_method', 'sendEmail');
        Config::set('mailgun.authorization.spatie_permission', 'send email');
    }

    private function createTestModel()
    {
        return new class
        {
            use CanSendEmails;
        };
    }

    #[Test]
    public function it_uses_gate_authorization_by_default()
    {
        $model = $this->createTestModel();

        Gate::shouldReceive('allows')
            ->with('send-email', $model)
            ->once()
            ->andReturn(true);

        $this->assertTrue($model->canSendEmails());
    }

    #[Test]
    public function it_uses_gate_authorization_when_configured()
    {
        $model = $this->createTestModel();

        Config::set('mailgun.authorization.method', 'gate');
        Config::set('mailgun.authorization.gate_name', 'custom-gate');

        Gate::shouldReceive('allows')
            ->with('custom-gate', $model)
            ->once()
            ->andReturn(false);

        $this->assertFalse($model->canSendEmails());
    }

    #[Test]
    public function it_uses_policy_authorization_when_configured()
    {
        Config::set('mailgun.authorization.method', 'policy');
        Config::set('mailgun.authorization.policy_method', 'customPolicy');

        $model = $this->createTestModel();

        // Add the can method to the anonymous class
        $modelWithCan = new class
        {
            use CanSendEmails;

            public function can($ability)
            {
                return $ability === 'customPolicy';
            }
        };

        $this->assertTrue($modelWithCan->canSendEmails());
    }

    #[Test]
    public function it_uses_spatie_authorization_when_configured()
    {
        Config::set('mailgun.authorization.method', 'spatie');
        Config::set('mailgun.authorization.spatie_permission', 'custom permission');

        $modelWithSpatie = new class
        {
            use CanSendEmails;

            public function hasPermissionTo($permission)
            {
                return false;
            }
        };

        $this->assertFalse($modelWithSpatie->canSendEmails());
    }

    #[Test]
    public function it_returns_false_when_policy_method_does_not_exist()
    {
        Config::set('mailgun.authorization.method', 'policy');

        $model = $this->createTestModel();

        // The model doesn't have the 'can' method, so it should return false
        $this->assertFalse($model->canSendEmails());
    }

    #[Test]
    public function it_returns_false_when_spatie_method_does_not_exist()
    {
        Config::set('mailgun.authorization.method', 'spatie');

        $model = $this->createTestModel();

        // The model doesn't have the 'hasPermissionTo' method, so it should return false
        $this->assertFalse($model->canSendEmails());
    }

    #[Test]
    public function it_allows_sending_when_no_auth_is_configured()
    {
        $model = $this->createTestModel();
        Config::set('mailgun.authorization.method', 'none');
        $this->assertTrue($model->canSendEmails());
    }
}
