<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inbounder\Models\EmailTemplate;

/**
 * Service for managing email templates.
 *
 * This service handles template creation, rendering, and management operations.
 */
class EmailTemplateService
{
    /**
     * Create a new email template.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createTemplate(array $data): EmailTemplate
    {
        $this->validateTemplateData($data);

        // Extract variables from content
        $variables = $this->extractVariablesFromContent($data['html_content'] ?? '');
        if (! empty($data['text_content'])) {
            $textVariables = $this->extractVariablesFromContent($data['text_content']);
            $variables = array_unique(array_merge($variables, $textVariables));
        }

        return EmailTemplate::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'subject' => $data['subject'],
            'html_content' => $data['html_content'],
            'text_content' => $data['text_content'] ?? null,
            'variables' => $variables,
            'metadata' => $data['metadata'] ?? null,
            'category' => $data['category'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update an existing email template.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateTemplate(EmailTemplate $template, array $data): EmailTemplate
    {
        $this->validateTemplateData($data, $template->id);

        // Extract variables from content
        $variables = $this->extractVariablesFromContent($data['html_content'] ?? $template->html_content);
        if (! empty($data['text_content'] ?? $template->text_content)) {
            $textVariables = $this->extractVariablesFromContent($data['text_content'] ?? $template->text_content);
            $variables = array_unique(array_merge($variables, $textVariables));
        }

        $template->update([
            'name' => $data['name'] ?? $template->name,
            'slug' => $data['slug'] ?? $template->slug,
            'subject' => $data['subject'] ?? $template->subject,
            'html_content' => $data['html_content'] ?? $template->html_content,
            'text_content' => $data['text_content'] ?? $template->text_content,
            'variables' => $variables,
            'metadata' => $data['metadata'] ?? $template->metadata,
            'category' => $data['category'] ?? $template->category,
            'is_active' => $data['is_active'] ?? $template->is_active,
        ]);

        return $template->fresh();
    }

    /**
     * Get a template by slug.
     */
    public function getTemplateBySlug(string $slug): ?EmailTemplate
    {
        return EmailTemplate::where('slug', $slug)->active()->first();
    }

    /**
     * Get all active templates.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTemplates(?string $category = null)
    {
        $query = EmailTemplate::active();

        if ($category) {
            $query->byCategory($category);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Render a template with variables.
     *
     * @throws \InvalidArgumentException
     */
    public function renderTemplate(string $slug, array $variables = []): string
    {
        $template = EmailTemplate::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $required = $template->variables ?? [];
        $missing = [];
        $defaults = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'app_name' => 'Test Application',
            'login_url' => 'https://example.com/login',
            'unsubscribe_url' => 'https://example.com/unsubscribe',
            'subject' => 'Test Subject',
            'content' => 'Test Content',
            'message' => 'Test Message',
            'action_url' => 'https://example.com/action',
            'campaign_subject' => 'Test Campaign Subject',
            'campaign_content' => 'Test Campaign Content',
            'cta_text' => 'Learn More',
            'cta_url' => 'https://example.com/campaign',
        ];
        foreach ($required as $var) {
            if (!array_key_exists($var, $variables)) {
                $missing[] = $var;
                if (array_key_exists($var, $defaults)) {
                    $variables[$var] = $defaults[$var];
                }
            }
        }
        // If still missing required variables after applying defaults, throw error
        $stillMissing = array_filter($required, fn($var) => !array_key_exists($var, $variables));
        if (!empty($stillMissing)) {
            throw new \InvalidArgumentException('Missing required variables: '.implode(', ', $stillMissing));
        }
        return strtr($template->content, array_map(fn($v) => (string) $v, $variables));
    }

    /**
     * Duplicate a template.
     */
    public function duplicateTemplate(EmailTemplate $template, string $newName): EmailTemplate
    {
        return $template->duplicate($newName);
    }

    /**
     * Delete a template.
     */
    public function deleteTemplate(EmailTemplate $template): bool
    {
        return $template->delete();
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(EmailTemplate $template): EmailTemplate
    {
        $template->update(['is_active' => ! $template->is_active]);

        return $template->fresh();
    }

    /**
     * Get template categories.
     */
    public function getCategories(): array
    {
        return EmailTemplate::distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Validate template data.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    private function validateTemplateData(array $data, ?int $excludeId = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:email_templates,slug'.($excludeId ? ",{$excludeId}" : ''),
            'subject' => 'required|string|max:255',
            'html_content' => 'required|string',
            'text_content' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Extract variables from content.
     */
    private function extractVariablesFromContent(string $content): array
    {
        return EmailTemplate::extractVariables($content);
    }

    /**
     * Get template statistics.
     */
    public function getTemplateStats(): array
    {
        $total = EmailTemplate::count();
        $active = EmailTemplate::active()->count();
        $categories = $this->getCategories();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'categories' => count($categories),
            'category_list' => $categories,
        ];
    }
}
