<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Models\EmailTemplate;
use Inbounder\Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_extract_variables_from_content()
    {
        $content = '<h1>Hello {{name}}</h1><p>Your email: {{ email }}</p>';
        $vars = EmailTemplate::extractVariables($content);
        $this->assertEqualsCanonicalizing(['name', 'email'], $vars);
    }

    public function test_render_html_and_text_with_variables()
    {
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'subject' => 'Hi {{name}}',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'text_content' => 'Hello {{name}}',
            'variables' => ['name'],
            'is_active' => true,
        ]);
        $vars = ['name' => 'Alice'];
        $this->assertEquals('<h1>Hello Alice</h1>', $template->renderHtml($vars));
        $this->assertEquals('Hello Alice', $template->renderText($vars));
        $this->assertEquals('Hi Alice', $template->renderSubject($vars));
    }

    public function test_validate_variables_and_missing()
    {
        $template = EmailTemplate::create([
            'name' => 'Vars',
            'slug' => 'vars',
            'subject' => 'Hi {{name}}',
            'html_content' => '<h1>Hello {{name}}</h1>',
            'variables' => ['name', 'email'],
            'is_active' => true,
        ]);
        $this->assertFalse($template->validateVariables(['name' => 'A']));
        $this->assertTrue($template->validateVariables(['name' => 'A', 'email' => 'a@b.com']));
        $this->assertEqualsCanonicalizing(['email'], $template->getMissingVariables(['name' => 'A']));
    }

    public function test_duplicate_template()
    {
        $template = EmailTemplate::create([
            'name' => 'Original',
            'slug' => 'original',
            'subject' => 'Hi',
            'html_content' => '<h1>Hi</h1>',
            'variables' => [],
            'is_active' => true,
        ]);
        $copy = $template->duplicate('Copy');
        $this->assertEquals('Copy', $copy->name);
        $this->assertEquals('copy', $copy->slug);
        $this->assertFalse($copy->is_active);
    }
}
