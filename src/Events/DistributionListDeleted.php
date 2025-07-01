<?php

declare(strict_types=1);

namespace Inbounder\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a distribution list is deleted.
 */
class DistributionListDeleted
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  int  $listId  The ID of the deleted list
     * @param  string  $listName  The name of the deleted list
     * @param  string  $listSlug  The slug of the deleted list
     * @param  string|null  $listCategory  The category of the deleted list
     * @param  bool  $wasActive  Whether the list was active when deleted
     * @param  array  $listData  Additional data about the deleted list
     */
    public function __construct(
        public readonly int $listId,
        public readonly string $listName,
        public readonly string $listSlug,
        public readonly ?string $listCategory,
        public readonly bool $wasActive,
        public readonly array $listData = []
    ) {}

    /**
     * Get the list ID.
     */
    public function getListId(): int
    {
        return $this->listId;
    }

    /**
     * Get the list name.
     */
    public function getListName(): string
    {
        return $this->listName;
    }

    /**
     * Get the list slug.
     */
    public function getListSlug(): string
    {
        return $this->listSlug;
    }

    /**
     * Get the list category.
     */
    public function getListCategory(): ?string
    {
        return $this->listCategory;
    }

    /**
     * Check if the list was active when deleted.
     */
    public function wasActive(): bool
    {
        return $this->wasActive;
    }

    /**
     * Get additional list data.
     */
    public function getListData(): array
    {
        return $this->listData;
    }

    /**
     * Get a specific piece of list data.
     */
    public function getListDataValue(string $key, mixed $default = null): mixed
    {
        return $this->listData[$key] ?? $default;
    }

    /**
     * Check if the list had subscribers when deleted.
     */
    public function hadSubscribers(): bool
    {
        return $this->getListDataValue('subscriber_count', 0) > 0;
    }

    /**
     * Get the number of subscribers the list had when deleted.
     */
    public function getSubscriberCount(): int
    {
        return $this->getListDataValue('subscriber_count', 0);
    }

    /**
     * Get the creation date of the deleted list.
     */
    public function getCreatedAt(): ?string
    {
        return $this->getListDataValue('created_at');
    }

    /**
     * Get the last update date of the deleted list.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getListDataValue('updated_at');
    }
}
