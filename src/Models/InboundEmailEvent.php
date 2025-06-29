<?php

namespace Fullstack\Inbounder\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundEmailEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbound_email_id',
        'event_type',
        'event_id',
        'event_data',
        'ip_address',
        'user_agent',
        'country',
        'region',
        'city',
        'device_type',
        'client_type',
        'client_name',
        'client_os',
        'url',
        'occurred_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the email this event belongs to.
     */
    public function email(): BelongsTo
    {
        return $this->belongsTo(InboundEmail::class, 'inbound_email_id');
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeByCountry($query, string $country)
    {
        return $query->where('country', $country);
    }

    /**
     * Scope to filter by device type.
     */
    public function scopeByDeviceType($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Get event types as constants.
     */
    public static function getEventTypes(): array
    {
        return [
            'delivered' => 'delivered',
            'opened' => 'opened',
            'clicked' => 'clicked',
            'bounced' => 'bounced',
            'dropped' => 'dropped',
            'complained' => 'complained',
            'unsubscribed' => 'unsubscribed',
        ];
    }
}
