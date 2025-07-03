<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inbounder\Models\EmailTemplate;
use App\Models\User;
use Inbounder\Models\MailgunOutboundEmail;

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
    public function renderTemplate(User $user, EmailTemplate $template, MailgunOutboundEmail $outboundEmail, array $variables = []): array
    {
        /**
         * Swap each of the variables in the template with the values from the outbound email.
         */
         $html_content = $template->html_content;

         foreach ($variables as $key => $value) {
            $html_content = str_replace('{{' . $key . '}}', $value, $html_content);
         }

        /**
         * Templates may or may not have variables to allow for dynamic content. If variables are present,
         * they are considered required.
         */
        $required = $template->variables ?? [];

        $missing = [];

        list($first_name, $last_name) = explode(' ', $user->name);

        /**
         * We make the following variables available to all templates by default.
         */
        $defaults = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'name' => $user->name,
            'email' => $user->email,

            // @todo This is a temporary solution to get the tenant name.
            'tenant_name' => '',

            // @todo We need to add the unsubscribe URL to the template.
            'unsubscribe_url' => '',

            // @todo We need to add the login URL to the template.
            'login_url' => '',

        ];

        foreach ($required as $var) {
            if (!array_key_exists($var, $variables)) {
                $missing[] = $var;
                if (array_key_exists($var, $defaults)) {
                    $variables[$var] = $defaults[$var];
                }
            }
        }

        /**
         * If there are still missing variables, throw an exception.
         */
        $stillMissing = array_filter($required, fn($var) => !array_key_exists($var, $variables));
        if (!empty($stillMissing)) {
            logger()->error('Missing required variables: '.implode(', ', $stillMissing));
        }

        return [
            'template' => $template,
            'subject' => $outboundEmail->subject,
            'html_content' => $html_content,
            'text_content' => $template->text_content,
        ];
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
