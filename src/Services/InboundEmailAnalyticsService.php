<?php

namespace Fullstack\Inbounder\Services;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Illuminate\Support\Facades\DB;

class InboundEmailAnalyticsService
{
    /**
     * Get email analytics for a specific period.
     */
    public function getAnalytics(Carbon $startDate, Carbon $endDate, ?int $tenantId = null): array
    {
        $query = InboundEmail::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $emails = $query->get();
        $emailIds = $emails->pluck('id');

        $events = InboundEmailEvent::whereIn('inbound_email_id', $emailIds)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'summary' => $this->getSummary($emails, $events),
            'events' => $this->getEventBreakdown($events),
            'geography' => $this->getGeographicData($events),
            'devices' => $this->getDeviceData($events),
            'clients' => $this->getClientData($events),
            'daily_trends' => $this->getDailyTrends($emails, $events),
        ];
    }

    /**
     * Get summary statistics.
     */
    private function getSummary($emails, $events): array
    {
        $totalEmails = $emails->count();
        $totalEvents = $events->count();

        $eventCounts = $events->groupBy('event_type')->map->count();

        return [
            'total_emails' => $totalEmails,
            'total_events' => $totalEvents,
            'delivered' => $eventCounts->get('delivered', 0),
            'opened' => $eventCounts->get('opened', 0),
            'clicked' => $eventCounts->get('clicked', 0),
            'bounced' => $eventCounts->get('bounced', 0),
            'dropped' => $eventCounts->get('dropped', 0),
            'complained' => $eventCounts->get('complained', 0),
            'unsubscribed' => $eventCounts->get('unsubscribed', 0),
            'open_rate' => $totalEmails > 0 ? round(($eventCounts->get('opened', 0) / $totalEmails) * 100, 2) : 0,
            'click_rate' => $totalEmails > 0 ? round(($eventCounts->get('clicked', 0) / $totalEmails) * 100, 2) : 0,
            'bounce_rate' => $totalEmails > 0 ? round(($eventCounts->get('bounced', 0) / $totalEmails) * 100, 2) : 0,
        ];
    }

    /**
     * Get event breakdown by type.
     */
    private function getEventBreakdown($events): array
    {
        $totalEvents = $events->count();

        return $events->groupBy('event_type')
            ->map(function ($group) use ($totalEvents) {
                return [
                    'count' => $group->count(),
                    'percentage' => $totalEvents > 0 ? round(($group->count() / $totalEvents) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    /**
     * Get geographic data.
     */
    private function getGeographicData($events): array
    {
        return $events->whereNotNull('country')
            ->groupBy('country')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'regions' => $group->whereNotNull('region')
                        ->groupBy('region')
                        ->map->count()
                        ->toArray(),
                ];
            })
            ->sortByDesc('count')
            ->take(10)
            ->toArray();
    }

    /**
     * Get device data.
     */
    private function getDeviceData($events): array
    {
        return $events->whereNotNull('device_type')
            ->groupBy('device_type')
            ->map->count()
            ->toArray();
    }

    /**
     * Get client data.
     */
    private function getClientData($events): array
    {
        return $events->whereNotNull('client_name')
            ->groupBy('client_name')
            ->map->count()
            ->sortByDesc(function ($count) {
                return $count;
            })
            ->take(10)
            ->toArray();
    }

    /**
     * Get daily trends.
     */
    private function getDailyTrends($emails, $events): array
    {
        $dailyEmails = $emails->groupBy(function ($email) {
            return $email->created_at->format('Y-m-d');
        })->map->count();

        $dailyEvents = $events->groupBy(function ($event) {
            return $event->occurred_at->format('Y-m-d');
        })->map(function ($dayEvents) {
            return $dayEvents->groupBy('event_type')->map->count();
        });

        $dates = collect(array_merge($dailyEmails->keys()->toArray(), $dailyEvents->keys()->toArray()))
            ->unique()
            ->sort()
            ->values();

        return $dates->map(function ($date) use ($dailyEmails, $dailyEvents) {
            $dayEvents = $dailyEvents->get($date, collect());

            return [
                'date' => $date,
                'emails' => $dailyEmails->get($date, 0),
                'delivered' => $dayEvents->get('delivered', 0),
                'opened' => $dayEvents->get('opened', 0),
                'clicked' => $dayEvents->get('clicked', 0),
                'bounced' => $dayEvents->get('bounced', 0),
            ];
        })->toArray();
    }

    /**
     * Export analytics data to CSV.
     */
    public function exportToCsv(Carbon $startDate, Carbon $endDate, ?int $tenantId = null): string
    {
        $analytics = $this->getAnalytics($startDate, $endDate, $tenantId);

        $filename = "inbound-email-analytics-{$startDate->format('Y-m-d')}-to-{$endDate->format('Y-m-d')}.csv";

        $handle = fopen('php://temp', 'r+');

        // Write headers
        fputcsv($handle, ['Metric', 'Value']);

        // Write summary data
        foreach ($analytics['summary'] as $key => $value) {
            fputcsv($handle, [ucfirst(str_replace('_', ' ', $key)), $value]);
        }

        // Write geographic data
        fputcsv($handle, []);
        fputcsv($handle, ['Top Countries']);
        foreach ($analytics['geography'] as $country => $data) {
            fputcsv($handle, [$country, $data['count']]);
        }

        // Write device data
        fputcsv($handle, []);
        fputcsv($handle, ['Device Types']);
        foreach ($analytics['devices'] as $device => $count) {
            fputcsv($handle, [$device, $count]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Get real-time metrics.
     */
    public function getRealTimeMetrics(?int $tenantId = null): array
    {
        $now = Carbon::now();
        $lastHour = $now->copy()->subHour();
        $last24Hours = $now->copy()->subDay();

        $query = InboundEmail::query();
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $emailsLastHour = $query->whereBetween('created_at', [$lastHour, $now])->count();
        $emailsLast24Hours = $query->whereBetween('created_at', [$last24Hours, $now])->count();

        return [
            'emails_last_hour' => $emailsLastHour,
            'emails_last_24_hours' => $emailsLast24Hours,
            'average_emails_per_hour' => $emailsLast24Hours / 24,
        ];
    }
}
