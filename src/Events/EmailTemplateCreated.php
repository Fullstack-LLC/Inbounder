<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Inbounder\Models\EmailTemplate;

/**
 * Event fired when an email template is created.
 */
class EmailTemplateCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly EmailTemplate $emailTemplate
    ) {}

    /**
     * Get the email template that was created.
     */
    public function getEmailTemplate(): EmailTemplate
    {
        return $this->emailTemplate;
    }

    /**
     * Get the template ID.
     */
    public function getTemplateId(): int
    {
        return $this->emailTemplate->id;
    }

    /**
     * Get the template name.
     */
    public function getTemplateName(): string
    {
        return $this->emailTemplate->name;
    }

    /**
     * Get the template slug.
     */
    public function getTemplateSlug(): string
    {
        return $this->emailTemplate->slug;
    }

    /**
     * Get the template subject.
     */
    public function getTemplateSubject(): string
    {
        return $this->emailTemplate->subject;
    }

    /**
     * Get the template category.
     */
    public function getTemplateCategory(): ?string
    {
        return $this->emailTemplate->category;
    }

    /**
     * Check if the template is active.
     */
    public function isActive(): bool
    {
        return $this->emailTemplate->is_active;
    }

    /**
     * Get the template variables.
     */
    public function getTemplateVariables(): array
    {
        return $this->emailTemplate->variables ?? [];
    }

    /**
     * Get the template metadata.
     */
    public function getTemplateMetadata(): array
    {
        return $this->emailTemplate->metadata ?? [];
    }

    /**
     * Check if the template has HTML content.
     */
    public function hasHtmlContent(): bool
    {
        return ! empty($this->emailTemplate->html_content);
    }

    /**
     * Check if the template has text content.
     */
    public function hasTextContent(): bool
    {
        return ! empty($this->emailTemplate->text_content);
    }

    /**
     * Get the content length (HTML + text).
     */
    public function getContentLength(): int
    {
        $length = strlen($this->emailTemplate->html_content ?? '');
        $length += strlen($this->emailTemplate->text_content ?? '');

        return $length;
    }

    /**
     * Get the number of variables in the template.
     */
    public function getVariableCount(): int
    {
        return count($this->getTemplateVariables());
    }
}
