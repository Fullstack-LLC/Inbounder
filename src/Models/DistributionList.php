<?php

declare(strict_types=1);

namespace Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inbounder\Events\DistributionListCreated;
use Inbounder\Events\DistributionListDeleted;
use Inbounder\Events\DistributionListUpdated;

/**
 * Distribution list model for managing email recipient groups.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property bool $is_active
 * @property string|null $category
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DistributionList extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'category',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Dispatch events for model lifecycle
        static::created(function (DistributionList $distributionList) {
            event(new DistributionListCreated($distributionList));
        });

        static::updated(function (DistributionList $distributionList) {
            $changes = [];
            foreach ($distributionList->getChanges() as $attribute => $newValue) {
                $changes[$attribute] = [
                    'old' => $distributionList->getOriginal($attribute),
                    'new' => $newValue,
                ];
            }
            event(new DistributionListUpdated($distributionList, $changes));
        });

        static::deleted(function (DistributionList $distributionList) {
            $listData = [
                'subscriber_count' => $distributionList->getSubscriberCount(),
                'total_subscriber_count' => $distributionList->getTotalSubscriberCount(),
                'created_at' => $distributionList->created_at?->toISOString(),
                'updated_at' => $distributionList->updated_at?->toISOString(),
            ];

            event(new DistributionListDeleted(
                $distributionList->id,
                $distributionList->name,
                $distributionList->slug,
                $distributionList->category,
                $distributionList->is_active,
                $listData
            ));
        });
    }

    /**
     * Scope to get only active lists.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Get the subscribers for this list.
     */
    public function subscribers(): HasMany
    {
        return $this->hasMany(DistributionListSubscriber::class, 'distribution_list_id');
    }

    /**
     * Get the active subscribers for this list.
     */
    public function activeSubscribers(): HasMany
    {
        return $this->hasMany(DistributionListSubscriber::class, 'distribution_list_id')
            ->where('is_active', true);
    }

    /**
     * Get the subscriber count for this list.
     */
    public function getSubscriberCount(): int
    {
        return $this->activeSubscribers()->count();
    }

    /**
     * Get the total subscriber count (including inactive).
     */
    public function getTotalSubscriberCount(): int
    {
        return $this->subscribers()->count();
    }

    /**
     * Check if a subscriber exists in this list.
     */
    public function hasSubscriber(?string $email = null, ?int $userId = null): bool
    {
        $query = $this->subscribers();
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($email) {
            $query->where('email', $email);
        } else {
            return false;
        }

        return $query->where('is_active', true)->exists();
    }

    /**
     * Add a subscriber to this list.
     *
     * @param  string|array  $data  Email string or array with user_id or email
     * @param  array  $additionalData  Additional data when email is passed as string
     */
    public function addSubscriber($data, array $additionalData = []): DistributionListSubscriber
    {
        // Handle case where email is passed as string
        if (is_string($data)) {
            $data = array_merge(['email' => $data], $additionalData);
        }

        $defaults = ['is_active' => true];
        $mergedData = array_merge($defaults, $data);

        if (isset($mergedData['user_id'])) {
            return $this->subscribers()->updateOrCreate(
                ['user_id' => $mergedData['user_id']],
                $mergedData
            );
        } elseif (isset($mergedData['email'])) {
            return $this->subscribers()->updateOrCreate(
                ['email' => $mergedData['email']],
                $mergedData
            );
        } else {
            throw new \InvalidArgumentException('Must provide user_id or email to addSubscriber');
        }
    }

    /**
     * Remove a subscriber from this list.
     */
    public function removeSubscriber(?int $userId = null, ?string $email = null): bool
    {
        $query = $this->subscribers();
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($email) {
            $query->where('email', $email);
        } else {
            return false;
        }

        return $query->delete() > 0;
    }

    /**
     * Get list categories.
     */
    public static function getCategories(): array
    {
        return static::distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();
    }

    /**
     * Get list statistics.
     */
    public function getStats(): array
    {
        $total = $this->getTotalSubscriberCount();
        $active = $this->getSubscriberCount();

        return [
            'total_subscribers' => $total,
            'active_subscribers' => $active,
            'inactive_subscribers' => $total - $active,
            'subscription_rate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }
}
