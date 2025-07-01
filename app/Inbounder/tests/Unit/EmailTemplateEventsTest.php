<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inbounder\Events\EmailTemplateCreated;
use Inbounder\Events\EmailTemplateDeleted;
use Inbounder\Events\EmailTemplateUpdated;
use Inbounder\Models\EmailTemplate;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

it('dispatches created event when email template is created', function () {
    Event::fake();

    $template = EmailTemplate::create([
        'name' => 'Test Template',
        'slug' => 'test-template',
        'subject' => 'Test Subject',
        'html_content' => '<h1>Hello {{name}}</h1>',
        'variables' => ['name'],
        'is_active' => true,
    ]);

    event(new EmailTemplateCreated($template));

    Event::assertDispatched(EmailTemplateCreated::class, function ($event) use ($template) {
        return $event->getEmailTemplate()->id === $template->id
            && $event->getTemplateName() === 'Test Template'
            && $event->isActive() === true;
    });
});

it('tests all getter methods of EmailTemplateCreated event', function () {
    $template = EmailTemplate::create([
        'name' => 'Marketing Email Template',
        'slug' => 'marketing-email-template',
        'subject' => 'Welcome to our newsletter!',
        'html_content' => '<h1>Hello {{name}}!</h1><p>Welcome to {{company}}.</p>',
        'text_content' => 'Hello {{name}}! Welcome to {{company}}.',
        'category' => 'Marketing',
        'variables' => ['name', 'company'],
        'metadata' => ['campaign_type' => 'newsletter', 'priority' => 'high'],
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test getEmailTemplate method
    $this->assertSame($template, $event->getEmailTemplate());
    $this->assertInstanceOf(EmailTemplate::class, $event->getEmailTemplate());

    // Test getTemplateId method
    $this->assertEquals($template->id, $event->getTemplateId());
    $this->assertIsInt($event->getTemplateId());

    // Test getTemplateName method
    $this->assertEquals('Marketing Email Template', $event->getTemplateName());
    $this->assertIsString($event->getTemplateName());

    // Test getTemplateSlug method
    $this->assertEquals('marketing-email-template', $event->getTemplateSlug());
    $this->assertIsString($event->getTemplateSlug());

    // Test getTemplateSubject method
    $this->assertEquals('Welcome to our newsletter!', $event->getTemplateSubject());
    $this->assertIsString($event->getTemplateSubject());

    // Test getTemplateCategory method
    $this->assertEquals('Marketing', $event->getTemplateCategory());
    $this->assertIsString($event->getTemplateCategory());

    // Test isActive method
    $this->assertTrue($event->isActive());
    $this->assertIsBool($event->isActive());

    // Test getTemplateVariables method
    $this->assertEquals(['name', 'company'], $event->getTemplateVariables());
    $this->assertIsArray($event->getTemplateVariables());

    // Test getTemplateMetadata method
    $this->assertEquals(['campaign_type' => 'newsletter', 'priority' => 'high'], $event->getTemplateMetadata());
    $this->assertIsArray($event->getTemplateMetadata());

    // Test hasHtmlContent method
    $this->assertTrue($event->hasHtmlContent());
    $this->assertIsBool($event->hasHtmlContent());

    // Test hasTextContent method
    $this->assertTrue($event->hasTextContent());
    $this->assertIsBool($event->hasTextContent());

    // Test getContentLength method
    $this->assertEquals(strlen($template->html_content) + strlen($template->text_content), $event->getContentLength());
    $this->assertIsInt($event->getContentLength());

    // Test getVariableCount method
    $this->assertEquals(2, $event->getVariableCount());
    $this->assertIsInt($event->getVariableCount());
});

it('tests EmailTemplateCreated event with null category', function () {
    $template = EmailTemplate::create([
        'name' => 'General Template',
        'slug' => 'general-template',
        'subject' => 'General Subject',
        'html_content' => '<h1>General</h1>',
        'category' => null,
        'variables' => [],
        'is_active' => false,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test getTemplateCategory method with null category
    $this->assertNull($event->getTemplateCategory());

    // Test isActive method with inactive template
    $this->assertFalse($event->isActive());

    // Test getTemplateVariables method with empty array
    $this->assertEquals([], $event->getTemplateVariables());
    $this->assertIsArray($event->getTemplateVariables());

    // Test getVariableCount method with empty variables
    $this->assertEquals(0, $event->getVariableCount());
});

it('tests EmailTemplateCreated event with empty content', function () {
    $template = EmailTemplate::create([
        'name' => 'Empty Content Template',
        'slug' => 'empty-content-template',
        'subject' => 'Empty Subject',
        'html_content' => '',
        'text_content' => '',
        'category' => 'Test',
        'variables' => [],
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test hasHtmlContent method with empty content
    $this->assertFalse($event->hasHtmlContent());

    // Test hasTextContent method with empty content
    $this->assertFalse($event->hasTextContent());

    // Test getContentLength method with empty content
    $this->assertEquals(0, $event->getContentLength());

    // Test getVariableCount method with empty variables
    $this->assertEquals(0, $event->getVariableCount());
});

it('tests EmailTemplateCreated event with special characters', function () {
    $template = EmailTemplate::create([
        'name' => 'Special & Characters Template!',
        'slug' => 'special-characters-template',
        'subject' => 'Special & Characters Subject!',
        'html_content' => '<h1>Hello {{name}} & {{company}}!</h1>',
        'text_content' => 'Hello {{name}} & {{company}}!',
        'category' => 'Special Category',
        'variables' => ['name', 'company'],
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test getTemplateName method with special characters
    $this->assertEquals('Special & Characters Template!', $event->getTemplateName());

    // Test getTemplateSubject method with special characters
    $this->assertEquals('Special & Characters Subject!', $event->getTemplateSubject());

    // Test getTemplateCategory method
    $this->assertEquals('Special Category', $event->getTemplateCategory());

    // Test hasHtmlContent method
    $this->assertTrue($event->hasHtmlContent());

    // Test hasTextContent method
    $this->assertTrue($event->hasTextContent());

    // Test getContentLength method
    $this->assertEquals(strlen($template->html_content) + strlen($template->text_content), $event->getContentLength());

    // Test getVariableCount method
    $this->assertEquals(2, $event->getVariableCount());
});

it('tests EmailTemplateCreated event with long values', function () {
    $longName = str_repeat('A', 255);
    $longSlug = str_repeat('a', 255);
    $longSubject = str_repeat('Subject ', 50);
    $longHtmlContent = str_repeat('<p>Long content</p>', 100);
    $longTextContent = str_repeat('Long text content ', 100);

    $template = EmailTemplate::create([
        'name' => $longName,
        'slug' => $longSlug,
        'subject' => $longSubject,
        'html_content' => $longHtmlContent,
        'text_content' => $longTextContent,
        'category' => 'Long Category',
        'variables' => ['var1', 'var2', 'var3', 'var4', 'var5'],
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test getTemplateName method with long name
    $this->assertEquals($longName, $event->getTemplateName());
    $this->assertEquals(255, strlen($event->getTemplateName()));

    // Test getTemplateSlug method with long slug
    $this->assertEquals($longSlug, $event->getTemplateSlug());
    $this->assertEquals(255, strlen($event->getTemplateSlug()));

    // Test getTemplateSubject method with long subject
    $this->assertEquals($longSubject, $event->getTemplateSubject());

    // Test getContentLength method with long content
    $this->assertEquals(strlen($longHtmlContent) + strlen($longTextContent), $event->getContentLength());

    // Test getVariableCount method with multiple variables
    $this->assertEquals(5, $event->getVariableCount());
});

it('tests EmailTemplateCreated event properties are accessible', function () {
    $template = EmailTemplate::create([
        'name' => 'Accessible Test Template',
        'slug' => 'accessible-test-template',
        'subject' => 'Accessible Subject',
        'html_content' => '<h1>Test</h1>',
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test that the emailTemplate property is accessible
    $this->assertSame($template, $event->emailTemplate);
    $this->assertInstanceOf(EmailTemplate::class, $event->emailTemplate);
});

it('tests EmailTemplateCreated event serialization', function () {
    $template = EmailTemplate::create([
        'name' => 'Serialization Test Template',
        'slug' => 'serialization-test-template',
        'subject' => 'Serialization Subject',
        'html_content' => '<h1>Serialization Test</h1>',
        'text_content' => 'Serialization Test',
        'category' => 'Test Category',
        'variables' => ['name', 'email'],
        'metadata' => ['test' => 'value'],
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test that the event can be serialized
    $serialized = serialize($event);
    $unserialized = unserialize($serialized);

    $this->assertInstanceOf(EmailTemplateCreated::class, $unserialized);
    $this->assertEquals($event->getTemplateId(), $unserialized->getTemplateId());
    $this->assertEquals($event->getTemplateName(), $unserialized->getTemplateName());
    $this->assertEquals($event->getTemplateSlug(), $unserialized->getTemplateSlug());
    $this->assertEquals($event->getTemplateSubject(), $unserialized->getTemplateSubject());
    $this->assertEquals($event->getTemplateCategory(), $unserialized->getTemplateCategory());
    $this->assertEquals($event->isActive(), $unserialized->isActive());
    $this->assertEquals($event->getTemplateVariables(), $unserialized->getTemplateVariables());
    $this->assertEquals($event->getTemplateMetadata(), $unserialized->getTemplateMetadata());
    $this->assertEquals($event->hasHtmlContent(), $unserialized->hasHtmlContent());
    $this->assertEquals($event->hasTextContent(), $unserialized->hasTextContent());
    $this->assertEquals($event->getContentLength(), $unserialized->getContentLength());
    $this->assertEquals($event->getVariableCount(), $unserialized->getVariableCount());
});

it('tests EmailTemplateCreated event with complex variables and metadata', function () {
    $template = EmailTemplate::create([
        'name' => 'Complex Template',
        'slug' => 'complex-template',
        'subject' => 'Complex Subject',
        'html_content' => '<h1>Hello {{name}}!</h1><p>Your email: {{email}}</p><p>Company: {{company}}</p>',
        'text_content' => 'Hello {{name}}! Your email: {{email}}. Company: {{company}}.',
        'category' => 'Complex Category',
        'variables' => ['name', 'email', 'company', 'role', 'department'],
        'metadata' => [
            'campaign_type' => 'onboarding',
            'priority' => 'high',
            'target_audience' => 'new_users',
            'send_frequency' => 'once',
            'tags' => ['welcome', 'onboarding', 'new_user']
        ],
        'is_active' => true,
    ]);

    $event = new EmailTemplateCreated($template);

    // Test getTemplateVariables method with complex variables
    $this->assertEquals(['name', 'email', 'company', 'role', 'department'], $event->getTemplateVariables());
    $this->assertEquals(5, $event->getVariableCount());

    // Test getTemplateMetadata method with complex metadata
    $metadata = $event->getTemplateMetadata();
    $this->assertEquals('onboarding', $metadata['campaign_type']);
    $this->assertEquals('high', $metadata['priority']);
    $this->assertEquals('new_users', $metadata['target_audience']);
    $this->assertEquals('once', $metadata['send_frequency']);
    $this->assertEquals(['welcome', 'onboarding', 'new_user'], $metadata['tags']);

    // Test content methods
    $this->assertTrue($event->hasHtmlContent());
    $this->assertTrue($event->hasTextContent());
    $this->assertGreaterThan(0, $event->getContentLength());
});

it('tests EmailTemplateCreated event with edge case content', function () {
    // Test with only HTML content
    $template1 = EmailTemplate::create([
        'name' => 'HTML Only Template',
        'slug' => 'html-only-template',
        'subject' => 'HTML Only Subject',
        'html_content' => '<h1>HTML Only</h1>',
        'text_content' => '',
        'variables' => [],
        'is_active' => true,
    ]);

    $event1 = new EmailTemplateCreated($template1);
    $this->assertTrue($event1->hasHtmlContent());
    $this->assertFalse($event1->hasTextContent());
    $this->assertEquals(strlen($template1->html_content), $event1->getContentLength()); // Only HTML content length

    // Test with only text content
    $template2 = EmailTemplate::create([
        'name' => 'Text Only Template',
        'slug' => 'text-only-template',
        'subject' => 'Text Only Subject',
        'html_content' => '',
        'text_content' => 'Text Only Content',
        'variables' => ['name'],
        'is_active' => false,
    ]);

    $event2 = new EmailTemplateCreated($template2);
    $this->assertFalse($event2->hasHtmlContent());
    $this->assertTrue($event2->hasTextContent());
    $this->assertEquals(strlen($template2->text_content), $event2->getContentLength()); // Only text content length
    $this->assertFalse($event2->isActive());
    $this->assertEquals(1, $event2->getVariableCount());

    // Test with null variables and metadata
    $template3 = EmailTemplate::create([
        'name' => 'Null Values Template',
        'slug' => 'null-values-template',
        'subject' => 'Null Values Subject',
        'html_content' => '<h1>Test</h1>',
        'variables' => null,
        'metadata' => null,
        'is_active' => true,
    ]);

    $event3 = new EmailTemplateCreated($template3);
    $this->assertEquals([], $event3->getTemplateVariables());
    $this->assertEquals([], $event3->getTemplateMetadata());
    $this->assertEquals(0, $event3->getVariableCount());
});

it('dispatches updated event when email template is updated', function () {
    $template = EmailTemplate::create([
        'name' => 'Original Template',
        'slug' => 'original-template',
        'subject' => 'Original Subject',
        'html_content' => '<h1>Original</h1>',
        'is_active' => false,
    ]);

    Event::fake([EmailTemplateUpdated::class]);

    $template->update([
        'name' => 'Updated Template',
        'is_active' => true,
    ]);

    Event::assertDispatched(EmailTemplateUpdated::class, function ($event) use ($template) {
        return $event->getEmailTemplate()->id === $template->id
            && $event->wasNameChanged() === true
            && $event->wasActivated() === true;
    });
});

it('dispatches deleted event when email template is deleted', function () {
    $template = EmailTemplate::create([
        'name' => 'Test Template',
        'slug' => 'test-template',
        'subject' => 'Test Subject',
        'html_content' => '<h1>Hello</h1>',
        'is_active' => true,
    ]);

    Event::fake([EmailTemplateDeleted::class]);

    $template->delete();

    Event::assertDispatched(EmailTemplateDeleted::class, function ($event) use ($template) {
        return $event->getTemplateId() === $template->id
            && $event->getTemplateName() === 'Test Template'
            && $event->wasActive() === true;
    });
});

class EmailTemplateEventsTest extends TestCase
{
    #[Test]
    public function email_template_created_event_has_correct_properties(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'category' => 'newsletter',
            'is_active' => true,
            'html_content' => '<h1>Hello</h1>',
            'text_content' => 'Hello',
            'variables' => ['name', 'email'],
            'metadata' => ['version' => '1.0'],
        ]);

        $event = new EmailTemplateCreated($template);

        $this->assertSame(1, $event->getTemplateId());
        $this->assertSame('Test Template', $event->getTemplateName());
        $this->assertSame('test-template', $event->getTemplateSlug());
        $this->assertSame('Test Subject', $event->getTemplateSubject());
        $this->assertSame('newsletter', $event->getTemplateCategory());
        $this->assertTrue($event->isActive());
        $this->assertSame($template, $event->getEmailTemplate());
    }

    #[Test]
    public function email_template_created_event_with_null_category(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'category' => null,
            'is_active' => false,
        ]);

        $event = new EmailTemplateCreated($template);

        $this->assertNull($event->getTemplateCategory());
        $this->assertFalse($event->isActive());
    }

    #[Test]
    public function email_template_created_event_properties_are_readonly(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
        ]);

        $event = new EmailTemplateCreated($template);

        // Test that the property is readonly by checking reflection
        $reflection = new \ReflectionClass($event);
        $property = $reflection->getProperty('emailTemplate');
        $this->assertTrue($property->isReadOnly());
    }

    #[Test]
    public function email_template_created_event_can_be_serialized(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
        ]);

        $event = new EmailTemplateCreated($template);
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(EmailTemplateCreated::class, $unserialized);
        $this->assertSame(1, $unserialized->getTemplateId());
    }

    #[Test]
    public function email_template_deleted_event_has_correct_properties(): void
    {
        $templateData = [
            'html_content' => '<h1>Hello</h1>',
            'text_content' => 'Hello',
            'variables' => ['name', 'email'],
            'metadata' => ['version' => '1.0'],
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-02 00:00:00',
        ];

        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: $templateData
        );

        $this->assertSame(1, $event->getTemplateId());
        $this->assertSame('Test Template', $event->getTemplateName());
        $this->assertSame('test-template', $event->getTemplateSlug());
        $this->assertSame('Test Subject', $event->getTemplateSubject());
        $this->assertSame('newsletter', $event->getTemplateCategory());
        $this->assertTrue($event->wasActive());
        $this->assertSame($templateData, $event->getTemplateData());
    }

    #[Test]
    public function email_template_deleted_event_with_null_category(): void
    {
        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: null,
            wasActive: false,
            templateData: []
        );

        $this->assertNull($event->getTemplateCategory());
        $this->assertFalse($event->wasActive());
    }

    #[Test]
    public function email_template_deleted_event_get_template_variables(): void
    {
        $templateData = [
            'variables' => ['name', 'email', 'company'],
            'metadata' => ['version' => '1.0'],
        ];

        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: $templateData
        );

        $this->assertSame(['name', 'email', 'company'], $event->getTemplateVariables());
        $this->assertSame(['version' => '1.0'], $event->getTemplateMetadata());
    }

    #[Test]
    public function email_template_deleted_event_get_template_variables_with_missing_data(): void
    {
        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: []
        );

        $this->assertSame([], $event->getTemplateVariables());
        $this->assertSame([], $event->getTemplateMetadata());
    }

    #[Test]
    public function email_template_deleted_event_content_length_calculations(): void
    {
        $templateData = [
            'html_content' => '<h1>Hello World</h1>',
            'text_content' => 'Hello World',
        ];

        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: $templateData
        );

        $this->assertSame(20, $event->getHtmlContentLength());
        $this->assertSame(11, $event->getTextContentLength());
        $this->assertSame(31, $event->getTotalContentLength());
    }

    #[Test]
    public function email_template_deleted_event_content_length_with_missing_content(): void
    {
        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: []
        );

        $this->assertSame(0, $event->getHtmlContentLength());
        $this->assertSame(0, $event->getTextContentLength());
        $this->assertSame(0, $event->getTotalContentLength());
    }

    #[Test]
    public function email_template_deleted_event_variable_count(): void
    {
        $templateData = [
            'variables' => ['name', 'email', 'company', 'position'],
        ];

        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: $templateData
        );

        $this->assertSame(4, $event->getVariableCount());
    }

    #[Test]
    public function email_template_deleted_event_dates(): void
    {
        $templateData = [
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-02 00:00:00',
        ];

        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: $templateData
        );

        $this->assertSame('2023-01-01 00:00:00', $event->getCreatedAt());
        $this->assertSame('2023-01-02 00:00:00', $event->getUpdatedAt());
    }

    #[Test]
    public function email_template_deleted_event_dates_with_missing_data(): void
    {
        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: []
        );

        $this->assertNull($event->getCreatedAt());
        $this->assertNull($event->getUpdatedAt());
    }

    #[Test]
    public function email_template_deleted_event_content_checks(): void
    {
        $eventWithHtml = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: ['html_content' => '<h1>Hello</h1>']
        );

        $eventWithText = new EmailTemplateDeleted(
            templateId: 2,
            templateName: 'Test Template 2',
            templateSlug: 'test-template-2',
            templateSubject: 'Test Subject 2',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: ['text_content' => 'Hello']
        );

        $eventWithBoth = new EmailTemplateDeleted(
            templateId: 3,
            templateName: 'Test Template 3',
            templateSlug: 'test-template-3',
            templateSubject: 'Test Subject 3',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: [
                'html_content' => '<h1>Hello</h1>',
                'text_content' => 'Hello'
            ]
        );

        $eventWithNone = new EmailTemplateDeleted(
            templateId: 4,
            templateName: 'Test Template 4',
            templateSlug: 'test-template-4',
            templateSubject: 'Test Subject 4',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: []
        );

        $this->assertTrue($eventWithHtml->hadHtmlContent());
        $this->assertFalse($eventWithHtml->hadTextContent());

        $this->assertFalse($eventWithText->hadHtmlContent());
        $this->assertTrue($eventWithText->hadTextContent());

        $this->assertTrue($eventWithBoth->hadHtmlContent());
        $this->assertTrue($eventWithBoth->hadTextContent());

        $this->assertFalse($eventWithNone->hadHtmlContent());
        $this->assertFalse($eventWithNone->hadTextContent());
    }

    #[Test]
    public function email_template_deleted_event_deletion_summary(): void
    {
        $templateData = [
            'html_content' => '<h1>Hello</h1>',
            'text_content' => 'Hello',
            'variables' => ['name', 'email'],
            'metadata' => ['version' => '1.0'],
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-02 00:00:00',
        ];

        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: $templateData
        );

        $summary = $event->getDeletionSummary();

        $this->assertSame([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'category' => 'newsletter',
            'was_active' => true,
            'content_length' => 20, // 20 + 0 (html: 20, text: 5, but text_content is not in templateData)
            'variable_count' => 2,
            'had_html_content' => true,
            'had_text_content' => true,
            'created_at' => '2023-01-01 00:00:00',
            'updated_at' => '2023-01-02 00:00:00',
        ], $summary);
    }

    #[Test]
    public function email_template_deleted_event_properties_are_readonly(): void
    {
        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: []
        );

        // Test that the property is readonly by checking reflection
        $reflection = new \ReflectionClass($event);
        $property = $reflection->getProperty('templateId');
        $this->assertTrue($property->isReadOnly());
    }

    #[Test]
    public function email_template_deleted_event_can_be_serialized(): void
    {
        $event = new EmailTemplateDeleted(
            templateId: 1,
            templateName: 'Test Template',
            templateSlug: 'test-template',
            templateSubject: 'Test Subject',
            templateCategory: 'newsletter',
            wasActive: true,
            templateData: ['variables' => ['name']]
        );

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(EmailTemplateDeleted::class, $unserialized);
        $this->assertSame(1, $unserialized->getTemplateId());
        $this->assertSame(['name'], $unserialized->getTemplateVariables());
    }

    #[Test]
    public function email_template_updated_event_has_correct_properties(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Updated Template',
            'slug' => 'updated-template',
            'subject' => 'Updated Subject',
            'category' => 'newsletter',
            'is_active' => true,
        ]);

        $changes = [
            'name' => ['old' => 'Old Template', 'new' => 'Updated Template'],
            'subject' => ['old' => 'Old Subject', 'new' => 'Updated Subject'],
        ];

        $event = new EmailTemplateUpdated($template, $changes);

        $this->assertSame($template, $event->getEmailTemplate());
        $this->assertSame(1, $event->getTemplateId());
        $this->assertSame('Updated Template', $event->getTemplateName());
        $this->assertSame('updated-template', $event->getTemplateSlug());
        $this->assertSame('Updated Subject', $event->getTemplateSubject());
        $this->assertSame('newsletter', $event->getTemplateCategory());
        $this->assertTrue($event->isActive());
        $this->assertSame($changes, $event->getChanges());
    }

    #[Test]
    public function email_template_updated_event_with_null_category(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'category' => null,
            'is_active' => false,
        ]);

        $event = new EmailTemplateUpdated($template, []);

        $this->assertNull($event->getTemplateCategory());
        $this->assertFalse($event->isActive());
    }

    #[Test]
    public function email_template_updated_event_change_detection(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
        ]);

        $changes = [
            'name' => ['old' => 'Old Name', 'new' => 'New Name'],
            'slug' => ['old' => 'old-slug', 'new' => 'new-slug'],
            'subject' => ['old' => 'Old Subject', 'new' => 'New Subject'],
            'html_content' => ['old' => '<h1>Old</h1>', 'new' => '<h1>New</h1>'],
            'text_content' => ['old' => 'Old text', 'new' => 'New text'],
            'variables' => ['old' => ['old'], 'new' => ['new']],
            'category' => ['old' => 'old-cat', 'new' => 'new-cat'],
            'is_active' => ['old' => false, 'new' => true],
        ];

        $event = new EmailTemplateUpdated($template, $changes);

        $this->assertTrue($event->wasChanged('name'));
        $this->assertTrue($event->wasChanged('slug'));
        $this->assertTrue($event->wasChanged('subject'));
        $this->assertTrue($event->wasChanged('html_content'));
        $this->assertTrue($event->wasChanged('text_content'));
        $this->assertTrue($event->wasChanged('variables'));
        $this->assertTrue($event->wasChanged('category'));
        $this->assertTrue($event->wasChanged('is_active'));

        $this->assertFalse($event->wasChanged('nonexistent'));
    }

    #[Test]
    public function email_template_updated_event_get_old_and_new_values(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        $changes = [
            'name' => ['old' => 'Old Name', 'new' => 'New Name'],
            'subject' => ['old' => 'Old Subject', 'new' => 'New Subject'],
        ];

        $event = new EmailTemplateUpdated($template, $changes);

        $this->assertSame('Old Name', $event->getOldValue('name'));
        $this->assertSame('New Name', $event->getNewValue('name'));
        $this->assertSame('Old Subject', $event->getOldValue('subject'));
        $this->assertSame('New Subject', $event->getNewValue('subject'));
        $this->assertNull($event->getOldValue('nonexistent'));
        $this->assertNull($event->getNewValue('nonexistent'));
    }

    #[Test]
    public function email_template_updated_event_specific_change_checks(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
        ]);

        $changes = [
            'name' => ['old' => 'Old Name', 'new' => 'New Name'],
            'slug' => ['old' => 'old-slug', 'new' => 'new-slug'],
            'subject' => ['old' => 'Old Subject', 'new' => 'New Subject'],
            'html_content' => ['old' => '<h1>Old</h1>', 'new' => '<h1>New</h1>'],
            'text_content' => ['old' => 'Old text', 'new' => 'New text'],
            'variables' => ['old' => ['old'], 'new' => ['new']],
            'category' => ['old' => 'old-cat', 'new' => 'new-cat'],
            'is_active' => ['old' => false, 'new' => true],
        ];

        $event = new EmailTemplateUpdated($template, $changes);

        $this->assertTrue($event->wasNameChanged());
        $this->assertTrue($event->wasSlugChanged());
        $this->assertTrue($event->wasSubjectChanged());
        $this->assertTrue($event->wasHtmlContentChanged());
        $this->assertTrue($event->wasTextContentChanged());
        $this->assertTrue($event->wereVariablesChanged());
        $this->assertTrue($event->wasCategoryChanged());
        $this->assertTrue($event->wasActiveStatusChanged());
    }

    #[Test]
    public function email_template_updated_event_activation_status(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        // Test activation
        $activationChanges = ['is_active' => ['old' => false, 'new' => true]];
        $activationEvent = new EmailTemplateUpdated($template, $activationChanges);

        $this->assertTrue($activationEvent->wasActivated());
        $this->assertFalse($activationEvent->wasDeactivated());

        // Test deactivation
        $deactivationChanges = ['is_active' => ['old' => true, 'new' => false]];
        $deactivationEvent = new EmailTemplateUpdated($template, $deactivationChanges);

        $this->assertFalse($deactivationEvent->wasActivated());
        $this->assertTrue($deactivationEvent->wasDeactivated());

        // Test no change
        $noChangeEvent = new EmailTemplateUpdated($template, []);

        $this->assertFalse($noChangeEvent->wasActivated());
        $this->assertFalse($noChangeEvent->wasDeactivated());
    }

    #[Test]
    public function email_template_updated_event_content_modification_check(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        // Test HTML content change
        $htmlChanges = ['html_content' => ['old' => '<h1>Old</h1>', 'new' => '<h1>New</h1>']];
        $htmlEvent = new EmailTemplateUpdated($template, $htmlChanges);

        $this->assertTrue($htmlEvent->wasContentModified());

        // Test text content change
        $textChanges = ['text_content' => ['old' => 'Old text', 'new' => 'New text']];
        $textEvent = new EmailTemplateUpdated($template, $textChanges);

        $this->assertTrue($textEvent->wasContentModified());

        // Test both content changes
        $bothChanges = [
            'html_content' => ['old' => '<h1>Old</h1>', 'new' => '<h1>New</h1>'],
            'text_content' => ['old' => 'Old text', 'new' => 'New text'],
        ];
        $bothEvent = new EmailTemplateUpdated($template, $bothChanges);

        $this->assertTrue($bothEvent->wasContentModified());

        // Test no content change
        $noContentEvent = new EmailTemplateUpdated($template, []);

        $this->assertFalse($noContentEvent->wasContentModified());
    }

    #[Test]
    public function email_template_updated_event_content_change_summary(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        $changes = [
            'html_content' => ['old' => '<h1>Hello</h1>', 'new' => '<h1>Hello World</h1>'],
            'text_content' => ['old' => 'Hello', 'new' => 'Hello World'],
        ];

        $event = new EmailTemplateUpdated($template, $changes);
        $summary = $event->getContentChangeSummary();

        $this->assertSame([
            'html_content' => [
                'old_length' => 13,
                'new_length' => 20,
            ],
            'text_content' => [
                'old_length' => 5,
                'new_length' => 11,
            ],
        ], $summary);
    }

    #[Test]
    public function email_template_updated_event_content_change_summary_with_missing_content(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        $changes = [
            'html_content' => ['old' => null, 'new' => '<h1>Hello</h1>'],
            'text_content' => ['old' => 'Hello', 'new' => null],
        ];

        $event = new EmailTemplateUpdated($template, $changes);
        $summary = $event->getContentChangeSummary();

        $this->assertSame([
            'html_content' => [
                'old_length' => 0,
                'new_length' => 13,
            ],
            'text_content' => [
                'old_length' => 5,
                'new_length' => 0,
            ],
        ], $summary);
    }

    #[Test]
    public function email_template_updated_event_properties_are_readonly(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        $event = new EmailTemplateUpdated($template, []);

        // Test that the property is readonly by checking reflection
        $reflection = new \ReflectionClass($event);
        $property = $reflection->getProperty('emailTemplate');
        $this->assertTrue($property->isReadOnly());
    }

    #[Test]
    public function email_template_updated_event_can_be_serialized(): void
    {
        $template = new EmailTemplate([
            'id' => 1,
            'name' => 'Test Template',
        ]);

        $changes = ['name' => ['old' => 'Old', 'new' => 'New']];
        $event = new EmailTemplateUpdated($template, $changes);

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(EmailTemplateUpdated::class, $unserialized);
        $this->assertSame(1, $unserialized->getTemplateId());
        $this->assertTrue($unserialized->wasNameChanged());
    }
}
