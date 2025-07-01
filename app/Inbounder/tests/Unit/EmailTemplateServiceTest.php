<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Models\EmailTemplate;
use Inbounder\Services\EmailTemplateService;
use Inbounder\Tests\TestCase;

class EmailTemplateServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmailTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new EmailTemplateService;
    }

    public function test_create_template_and_extracts_variables()
    {
        $data = [
            'name' => 'Welcome',
            'subject' => 'Hi {{name}}',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'text_content' => 'Hello {{name}}',
        ];
        $template = $this->service->createTemplate($data);
        $this->assertEquals(['name'], $template->variables);
        $this->assertEquals('Welcome', $template->name);
    }

    public function test_update_template_and_merges_variables()
    {
        $template = EmailTemplate::create([
            'name' => 'Update',
            'slug' => 'update',
            'subject' => 'Hi',
            'html_content' => '<h1>Hi</h1>',
            'variables' => [],
            'is_active' => true,
        ]);
        $updated = $this->service->updateTemplate($template, [
            'name' => 'Update',
            'subject' => 'Hi',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'text_content' => 'Hi {{email}}',
        ]);
        $this->assertEqualsCanonicalizing(['name', 'email'], $updated->variables);
    }

    public function test_render_template_success()
    {
        $template = EmailTemplate::create([
            'name' => 'Render',
            'slug' => 'render',
            'subject' => 'Hi {{name}}',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'variables' => ['name'],
            'is_active' => true,
        ]);
        $result = $this->service->renderTemplate('render', ['name' => 'Bob']);
        $this->assertEquals('Hi Bob', $result['subject']);
        $this->assertEquals('<h1>Hello Bob</h1>', $result['html_content']);
    }

    public function test_render_template_missing_variables_throws()
    {
        $template = EmailTemplate::create([
            'name' => 'Err',
            'slug' => 'err',
            'subject' => 'Hi {{name}}',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'variables' => ['name', 'email'],
            'is_active' => true,
        ]);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->renderTemplate('err', ['name' => 'Bob']);
    }

    public function test_duplicate_and_delete_template()
    {
        $template = EmailTemplate::create([
            'name' => 'Dup',
            'slug' => 'dup',
            'subject' => 'Hi',
            'html_content' => '<h1>Hi</h1>',
            'variables' => [],
            'is_active' => true,
        ]);
        $copy = $this->service->duplicateTemplate($template, 'Dup2');
        $this->assertEquals('Dup2', $copy->name);
        $this->assertTrue($this->service->deleteTemplate($copy));
    }

    public function test_toggle_active()
    {
        $template = EmailTemplate::create([
            'name' => 'Active',
            'slug' => 'active',
            'subject' => 'Hi',
            'html_content' => '<h1>Hi</h1>',
            'variables' => [],
            'is_active' => true,
        ]);
        $toggled = $this->service->toggleActive($template);
        $this->assertFalse($toggled->is_active);
    }
}
