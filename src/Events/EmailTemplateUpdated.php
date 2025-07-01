<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Inbounder\Models\EmailTemplate;

/**
 * Event fired when an email template is updated.
 */
class EmailTemplateUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array  $changes  The attributes that were changed
     */
    public function __construct(
        public readonly EmailTemplate $emailTemplate,
        public readonly array $changes
    ) {}

    /**
     * Get the email template that was updated.
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
     * Get the changes that were made.
     */
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * Check if a specific attribute was changed.
     */
    public function wasChanged(string $attribute): bool
    {
        return array_key_exists($attribute, $this->changes);
    }

    /**
     * Get the old value of a changed attribute.
     *
     * @return mixed|null
     */
    public function getOldValue(string $attribute): mixed
    {
        return $this->changes[$attribute]['old'] ?? null;
    }

    /**
     * Get the new value of a changed attribute.
     *
     * @return mixed|null
     */
    public function getNewValue(string $attribute): mixed
    {
        return $this->changes[$attribute]['new'] ?? null;
    }

    /**
     * Check if the name was changed.
     */
    public function wasNameChanged(): bool
    {
        return $this->wasChanged('name');
    }

    /**
     * Check if the slug was changed.
     */
    public function wasSlugChanged(): bool
    {
        return $this->wasChanged('slug');
    }

    /**
     * Check if the subject was changed.
     */
    public function wasSubjectChanged(): bool
    {
        return $this->wasChanged('subject');
    }

    /**
     * Check if the HTML content was changed.
     */
    public function wasHtmlContentChanged(): bool
    {
        return $this->wasChanged('html_content');
    }

    /**
     * Check if the text content was changed.
     */
    public function wasTextContentChanged(): bool
    {
        return $this->wasChanged('text_content');
    }

    /**
     * Check if the variables were changed.
     */
    public function wereVariablesChanged(): bool
    {
        return $this->wasChanged('variables');
    }

    /**
     * Check if the category was changed.
     */
    public function wasCategoryChanged(): bool
    {
        return $this->wasChanged('category');
    }

    /**
     * Check if the active status was changed.
     */
    public function wasActiveStatusChanged(): bool
    {
        return $this->wasChanged('is_active');
    }

    /**
     * Check if the template was activated.
     */
    public function wasActivated(): bool
    {
        return $this->wasActiveStatusChanged() && $this->getNewValue('is_active') === true;
    }

    /**
     * Check if the template was deactivated.
     */
    public function wasDeactivated(): bool
    {
        return $this->wasActiveStatusChanged() && $this->getNewValue('is_active') === false;
    }

    /**
     * Check if content was modified (HTML or text).
     */
    public function wasContentModified(): bool
    {
        return $this->wasHtmlContentChanged() || $this->wasTextContentChanged();
    }

    /**
     * Get the content change summary.
     */
    public function getContentChangeSummary(): array
    {
        $summary = [];

        if ($this->wasHtmlContentChanged()) {
            $summary['html_content'] = [
                'old_length' => strlen($this->getOldValue('html_content') ?? ''),
                'new_length' => strlen($this->getNewValue('html_content') ?? ''),
            ];
        }

        if ($this->wasTextContentChanged()) {
            $summary['text_content'] = [
                'old_length' => strlen($this->getOldValue('text_content') ?? ''),
                'new_length' => strlen($this->getNewValue('text_content') ?? ''),
            ];
        }

        return $summary;
    }
}
