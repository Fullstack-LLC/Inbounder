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

}
