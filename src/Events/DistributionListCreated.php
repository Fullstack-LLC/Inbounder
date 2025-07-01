<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Inbounder\Models\DistributionList;

/**
 * Event fired when a distribution list is created.
 */
class DistributionListCreated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly DistributionList $distributionList
    ) {}

    /**
     * Get the distribution list that was created.
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
}
