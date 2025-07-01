<?php

declare(strict_types=1);

namespace Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Distribution list subscriber model.
 *
 * @property int $id
 * @property int $distribution_list_id
 * @property string $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property bool $is_active
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class DistributionListSubscriber extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'distribution_list_id',
        'user_id',
        'email',
        'is_active',
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
     * Get the distribution list this subscriber belongs to.
     */
    public function distributionList(): BelongsTo
    {
        return $this->belongsTo(DistributionList::class, 'distribution_list_id');
    }

    /**
     * Get the user this subscriber refers to.
     */
    public function user(): BelongsTo
    {
        $userModel = config('mailgun.user_model', \App\Models\User::class);
        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Get the full name of the subscriber.
     */
    public function getFullName(): string
    {
        if ($this->user) {
            return trim($this->user->name);
        }

        return $this->email ?: '';
    }

    /**
     * Scope to get only active subscribers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by email domain.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('email', 'like', "%@{$domain}");
    }
}
