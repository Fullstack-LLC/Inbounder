<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Inbounder\Examples\UserWithEmailTrait;
use Inbounder\Models\DistributionList;
use Inbounder\Models\EmailTemplate;
use PHPUnit\Framework\Attributes\Test;
use Inbounder\Tests\TestCase;

class UserWithEmailTraitTest extends TestCase
{
    private UserWithEmailTrait $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new UserWithEmailTrait();
    }

    #[Test]
    public function it_uses_can_send_emails_trait()
    {
        $this->assertTrue(method_exists($this->user, 'canSendEmails'));
    }

    #[Test]
    public function it_returns_true_for_can_access_email_features_when_authorized()
    {
        // Mock the canSendEmails method to return true
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(true);

        $result = $this->user->canAccessEmailFeatures();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_for_can_access_email_features_when_not_authorized()
    {
        // Mock the canSendEmails method to return false
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(false);

        $result = $this->user->canAccessEmailFeatures();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_throws_exception_when_sending_email_to_distribution_list_without_authorization()
    {
        // Mock the canSendEmails method to return false
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(false);

        $distributionList = new DistributionList();
        $template = new EmailTemplate();
        $variables = ['name' => 'John Doe'];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User is not authorized to send emails.');

        $this->user->sendEmailToDistributionList($distributionList, $template, $variables);
    }

    #[Test]
    public function it_does_not_throw_exception_when_sending_email_to_distribution_list_with_authorization()
    {
        // Mock the canSendEmails method to return true
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(true);

        $distributionList = new DistributionList();
        $template = new EmailTemplate();
        $variables = ['name' => 'John Doe'];

        // Should not throw an exception
        $this->expectNotToPerformAssertions();

        $this->user->sendEmailToDistributionList($distributionList, $template, $variables);
    }

    #[Test]
    public function it_does_not_throw_exception_when_sending_email_to_distribution_list_with_authorization_and_empty_variables()
    {
        // Mock the canSendEmails method to return true
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(true);

        $distributionList = new DistributionList();
        $template = new EmailTemplate();

        // Should not throw an exception
        $this->expectNotToPerformAssertions();

        $this->user->sendEmailToDistributionList($distributionList, $template);
    }

    #[Test]
    public function it_extends_authenticatable_user()
    {
        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $this->user);
    }

    #[Test]
    public function it_has_send_email_to_distribution_list_method()
    {
        $this->assertTrue(method_exists($this->user, 'sendEmailToDistributionList'));
    }

    #[Test]
    public function it_has_can_access_email_features_method()
    {
        $this->assertTrue(method_exists($this->user, 'canAccessEmailFeatures'));
    }

    #[Test]
    public function it_uses_can_send_emails_trait_methods()
    {
        // Test that the trait methods are available
        $this->assertTrue(method_exists($this->user, 'canSendEmails'));

        // Test that the method can be called (will use default behavior from trait)
        $result = $this->user->canSendEmails();

        // The result depends on the current configuration, but the method should be callable
        $this->assertIsBool($result);
    }

    #[Test]
    public function it_handles_complex_variables_in_send_email_method()
    {
        // Mock the canSendEmails method to return true
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(true);

        $distributionList = new DistributionList();
        $template = new EmailTemplate();
        $variables = [
            'name' => 'John Doe',
            'company' => 'Acme Corp',
            'role' => 'Manager',
            'department' => 'Engineering',
            'custom_field' => 'Custom Value',
        ];

        // Should not throw an exception
        $this->expectNotToPerformAssertions();

        $this->user->sendEmailToDistributionList($distributionList, $template, $variables);
    }

    #[Test]
    public function it_handles_null_variables_in_send_email_method()
    {
        // Mock the canSendEmails method to return true
        $this->user = $this->createPartialMock(UserWithEmailTrait::class, ['canSendEmails']);
        $this->user->method('canSendEmails')->willReturn(true);

        $distributionList = new DistributionList();
        $template = new EmailTemplate();
        $variables = null;

        // Should not throw an exception
        $this->expectNotToPerformAssertions();

        $this->user->sendEmailToDistributionList($distributionList, $template, $variables);
    }
}
