<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an email template is deleted.
 */
class EmailTemplateDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array  $templateData  Additional template data
     */
    public function __construct(
        public readonly int $templateId,
        public readonly string $templateName,
        public readonly string $templateSlug,
        public readonly string $templateSubject,
        public readonly ?string $templateCategory,
        public readonly bool $wasActive,
        public readonly array $templateData
    ) {}

    /**
     * Get the template ID.
     */
    public function getTemplateId(): int
    {
        return $this->templateId;
    }

    /**
     * Get the template name.
     */
    public function getTemplateName(): string
    {
        return $this->templateName;
    }

    /**
     * Get the template slug.
     */
    public function getTemplateSlug(): string
    {
        return $this->templateSlug;
    }

    /**
     * Get the template subject.
     */
    public function getTemplateSubject(): string
    {
        return $this->templateSubject;
    }

    /**
     * Get the template category.
     */
    public function getTemplateCategory(): ?string
    {
        return $this->templateCategory;
    }

    /**
     * Check if the template was active when deleted.
     */
    public function wasActive(): bool
    {
        return $this->wasActive;
    }

    /**
     * Get the template data.
     */
    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    /**
     * Get the template variables.
     */
    public function getTemplateVariables(): array
    {
        return $this->templateData['variables'] ?? [];
    }

    /**
     * Get the template metadata.
     */
    public function getTemplateMetadata(): array
    {
        return $this->templateData['metadata'] ?? [];
    }

    /**
     * Get the HTML content length.
     */
    public function getHtmlContentLength(): int
    {
        return strlen($this->templateData['html_content'] ?? '');
    }

    /**
     * Get the text content length.
     */
    public function getTextContentLength(): int
    {
        return strlen($this->templateData['text_content'] ?? '');
    }

    /**
     * Get the total content length.
     */
    public function getTotalContentLength(): int
    {
        return $this->getHtmlContentLength() + $this->getTextContentLength();
    }

    /**
     * Get the number of variables.
     */
    public function getVariableCount(): int
    {
        return count($this->getTemplateVariables());
    }

    /**
     * Get the creation date.
     */
    public function getCreatedAt(): ?string
    {
        return $this->templateData['created_at'] ?? null;
    }

    /**
     * Get the last update date.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->templateData['updated_at'] ?? null;
    }

    /**
     * Check if the template had HTML content.
     */
    public function hadHtmlContent(): bool
    {
        return ! empty($this->templateData['html_content']);
    }

    /**
     * Check if the template had text content.
     */
    public function hadTextContent(): bool
    {
        return ! empty($this->templateData['text_content']);
    }

    /**
     * Get a summary of the deleted template.
     */
    public function getDeletionSummary(): array
    {
        return [
            'id' => $this->templateId,
            'name' => $this->templateName,
            'slug' => $this->templateSlug,
            'subject' => $this->templateSubject,
            'category' => $this->templateCategory,
            'was_active' => $this->wasActive,
            'content_length' => $this->getTotalContentLength(),
            'variable_count' => $this->getVariableCount(),
            'had_html_content' => $this->hadHtmlContent(),
            'had_text_content' => $this->hadTextContent(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
