<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inbounder\Mail\TemplatedEmail;
use Inbounder\Models\EmailTemplate;
use Inbounder\Tests\TestCase;

class TemplatedEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test template
        EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Hello {{name}}',
            'html_content' => '<h1>Welcome {{name}}!</h1><p>Your email: {{email}}</p>',
            'text_content' => 'Welcome {{name}}! Your email: {{email}}',
            'variables' => ['name', 'email'],
            'is_active' => true,
        ]);
    }

    public function test_templated_email_renders_correctly()
    {
        $variables = ['name' => 'John', 'email' => 'john@example.com'];
        $mailable = new TemplatedEmail('test-template', $variables);

        $this->assertEquals('Hello John', $mailable->subject);
        $this->assertEquals('<h1>Welcome John!</h1><p>Your email: john@example.com</p>', $mailable->template->renderHtml($variables));
        $this->assertEquals('Welcome John! Your email: john@example.com', $mailable->template->renderText($variables));
    }

    public function test_templated_email_with_custom_options()
    {
        $variables = ['name' => 'Jane', 'email' => 'jane@example.com'];
        $options = [
            'from' => [
                'address' => 'custom@example.com',
                'name' => 'Custom Sender',
            ],
            'reply_to' => [
                'address' => 'reply@example.com',
                'name' => 'Reply To',
            ],
        ];

        $mailable = new TemplatedEmail('test-template', $variables, $options);

        $this->assertEquals('Hello Jane', $mailable->subject);
        $this->assertEquals('custom@example.com', $mailable->from[0]['address']);
        $this->assertEquals('Custom Sender', $mailable->from[0]['name']);
    }

    public function test_templated_email_build_method()
    {
        $variables = ['name' => 'Alice', 'email' => 'alice@example.com'];
        $mailable = new TemplatedEmail('test-template', $variables);

        $built = $mailable->build();

        $this->assertInstanceOf(TemplatedEmail::class, $built);
        $this->assertEquals('Hello Alice', $built->subject);
    }

    public function test_templated_email_content_methods()
    {
        $variables = ['name' => 'Bob', 'email' => 'bob@example.com'];
        $mailable = new TemplatedEmail('test-template', $variables);

        $envelope = $mailable->envelope();
        $content = $mailable->content();
        $attachments = $mailable->attachments();

        $this->assertEquals('Hello Bob', $envelope->subject);
        $this->assertEquals('<h1>Welcome Bob!</h1><p>Your email: bob@example.com</p>', $content->htmlString);
        $this->assertEmpty($attachments);
    }

    public function test_templated_email_with_invalid_template_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        new TemplatedEmail('non-existent-template', []);
    }

    public function test_templated_email_with_missing_variables_throws_exception()
    {
        $this->expectException(\InvalidArgumentException::class);
        new TemplatedEmail('test-template', ['name' => 'John']); // Missing 'email'
    }

    public function test_templated_email_can_be_sent()
    {
        Mail::fake();

        $variables = ['name' => 'Test User', 'email' => 'test@example.com'];
        $mailable = new TemplatedEmail('test-template', $variables);

        Mail::to('recipient@example.com')->send($mailable);

        Mail::assertSent(TemplatedEmail::class, function ($mail) {
            return $mail->subject === 'Hello Test User';
        });
    }

    public function test_templated_email_has_template_and_variables_properties()
    {
        $variables = ['name' => 'Charlie', 'email' => 'charlie@example.com'];
        $mailable = new TemplatedEmail('test-template', $variables);

        $this->assertInstanceOf(EmailTemplate::class, $mailable->template);
        $this->assertEquals($variables, $mailable->variables);
        $this->assertEquals('test-template', $mailable->template->slug);
    }
}
