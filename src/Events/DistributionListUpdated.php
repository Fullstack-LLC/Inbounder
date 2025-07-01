<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Inbounder\Models\DistributionList;

/**
 * Event fired when a distribution list is updated.
 */
class DistributionListUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array  $changes  The attributes that were changed
     */
    public function __construct(
        public readonly DistributionList $distributionList,
        public readonly array $changes
    ) {}

    /**
     * Get the distribution list that was updated.
     */
    public function getDistributionList(): DistributionList
    {
        return $this->distributionList;
    }

    /**
     * Get the list ID.
     */
    public function getListId(): int
    {
        return $this->distributionList->id;
    }

    /**
     * Get the list name.
     */
    public function getListName(): string
    {
        return $this->distributionList->name;
    }

    /**
     * Get the list slug.
     */
    public function getListSlug(): string
    {
        return $this->distributionList->slug;
    }

    /**
     * Get the list category.
     */
    public function getListCategory(): ?string
    {
        return $this->distributionList->category;
    }

    /**
     * Check if the list is active.
     */
    public function isActive(): bool
    {
        return $this->distributionList->is_active;
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
     * Check if the list was activated.
     */
    public function wasActivated(): bool
    {
        return $this->wasActiveStatusChanged() && $this->getNewValue('is_active') === true;
    }

    /**
     * Check if the list was deactivated.
     */
    public function wasDeactivated(): bool
    {
        return $this->wasActiveStatusChanged() && $this->getNewValue('is_active') === false;
    }
}
