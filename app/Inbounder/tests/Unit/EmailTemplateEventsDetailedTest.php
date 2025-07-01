<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Inbounder\Events\EmailTemplateDeleted;
use Inbounder\Events\EmailTemplateUpdated;
use Inbounder\Models\EmailTemplate;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EmailTemplateEventsDetailedTest extends TestCase
{
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
            'content_length' => 19, // 20 + 0 (html: 20, text: 5, but text_content is not in templateData)
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
        $template = EmailTemplate::create([
            'name' => 'Updated Template',
            'slug' => 'updated-template',
            'subject' => 'Updated Subject',
            'html_content' => '<h1>Updated Template</h1>',
            'category' => 'newsletter',
            'is_active' => true,
        ]);

        $changes = [
            'name' => ['old' => 'Old Template', 'new' => 'Updated Template'],
            'subject' => ['old' => 'Old Subject', 'new' => 'Updated Subject'],
        ];

        $event = new EmailTemplateUpdated($template, $changes);

        $this->assertSame($template, $event->getEmailTemplate());
        $this->assertSame($template->id, $event->getTemplateId());
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
        ]);

        $changes = [
            'html_content' => ['old' => '<h1>Hello</h1>', 'new' => '<h1>Hello World</h1>'],
            'text_content' => ['old' => 'Hello', 'new' => 'Hello World'],
        ];

        $event = new EmailTemplateUpdated($template, $changes);
        $summary = $event->getContentChangeSummary();

        $this->assertSame([
            'html_content' => [
                'old_length' => 14,
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
                'new_length' => 14,
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
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
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Test Subject',
            'html_content' => '<h1>Test Template</h1>',
        ]);

        $changes = ['name' => ['old' => 'Old', 'new' => 'New']];
        $event = new EmailTemplateUpdated($template, $changes);

        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(EmailTemplateUpdated::class, $unserialized);
        $this->assertSame($template->id, $unserialized->getTemplateId());
        $this->assertTrue($unserialized->wasNameChanged());
    }
}
