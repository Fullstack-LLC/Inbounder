<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Fullstack\Inbounder\Models\InboundEmail;
use Fullstack\Inbounder\Models\InboundEmailEvent;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test emails for analytics data
        $emails = [
            [
                'message_id' => '<email1@example.com>',
                'from_email' => 'sender1@example.com',
                'to_email' => 'recipient1@example.com',
                'subject' => 'Test Email 1',
                'sender_id' => 1,
                'tenant_id' => 1,
                'created_at' => Carbon::now()->subDays(5),
            ],
            [
                'message_id' => '<email2@example.com>',
                'from_email' => 'sender2@example.com',
                'to_email' => 'recipient2@example.com',
                'subject' => 'Test Email 2',
                'sender_id' => 1,
                'tenant_id' => 1,
                'created_at' => Carbon::now()->subDays(3),
            ],
            [
                'message_id' => '<email3@example.com>',
                'from_email' => 'sender3@example.com',
                'to_email' => 'recipient3@example.com',
                'subject' => 'Test Email 3',
                'sender_id' => 1,
                'tenant_id' => 1,
                'created_at' => Carbon::now()->subDays(1),
            ],
        ];
        foreach ($emails as $emailData) {
            $email = InboundEmail::create($emailData);
            // Create events for each email
            $events = [
                [
                    'event_type' => 'delivered',
                    'ip_address' => '192.168.1.1',
                    'country' => 'United States',
                    'region' => 'California',
                    'city' => 'San Francisco',
                    'device_type' => 'desktop',
                    'client_type' => 'webmail',
                    'client_name' => 'Gmail',
                    'occurred_at' => Carbon::now()->subDays(4),
                ],
                [
                    'event_type' => 'opened',
                    'ip_address' => '192.168.1.2',
                    'country' => 'United States',
                    'region' => 'New York',
                    'city' => 'New York',
                    'device_type' => 'mobile',
                    'client_type' => 'mobile',
                    'client_name' => 'iOS Mail',
                    'occurred_at' => Carbon::now()->subDays(2),
                ],
            ];
            foreach ($events as $eventData) {
                $eventData['inbound_email_id'] = $email->id;
                InboundEmailEvent::create($eventData);
            }
        }
    }
}
