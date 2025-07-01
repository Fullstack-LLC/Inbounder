<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\DistributionLists;

use Illuminate\Console\Command;
use Inbounder\Models\DistributionList;
use Inbounder\Models\DistributionListSubscriber;
use Inbounder\Services\DistributionListService;

/**
 * Command to manage individual subscribers in distribution lists.
 */
class ManageSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:manage-subscribers
                            {list : List slug}
                            {--email= : Email address to manage}
                            {--show : Show subscriber details}
                            {--activate : Activate subscriber}
                            {--deactivate : Deactivate subscriber}
                            {--update : Update subscriber information}
                            {--export : Export subscribers to CSV}
                            {--search= : Search subscribers by email or name}
                            {--status= : Filter by status (active/inactive)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage individual subscribers in a distribution list';

    /**
     * The distribution list service instance.
     */
    private DistributionListService $listService;

    /**
     * Create a new command instance.
     */
    public function __construct(DistributionListService $listService)
    {
        parent::__construct();
        $this->listService = $listService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $listSlug = $this->argument('list');
        $list = $this->listService->getListBySlug($listSlug);

        if (! $list) {
            $this->error("❌ List '{$listSlug}' not found or inactive.");

            return Command::FAILURE;
        }

        $this->info("📧 Managing subscribers for list: {$list->name}");

        if ($email = $this->option('email')) {
            return $this->manageIndividualSubscriber($list, $email);
        }

        if ($this->option('export')) {
            return $this->exportSubscribers($list);
        }

        if ($search = $this->option('search')) {
            return $this->searchSubscribers($list, $search);
        }

        return $this->listSubscribers($list);
    }

    /**
     * Manage an individual subscriber.
     */
    private function manageIndividualSubscriber(DistributionList $list, string $email): int
    {
        $subscriber = $list->subscribers()->where('email', $email)->first();

        if (! $subscriber) {
            $this->error("❌ Subscriber '{$email}' not found in list '{$list->name}'.");

            return Command::FAILURE;
        }

        if ($this->option('show')) {
            return $this->showSubscriberDetails($subscriber);
        }

        if ($this->option('activate')) {
            return $this->activateSubscriber($subscriber);
        }

        if ($this->option('deactivate')) {
            return $this->deactivateSubscriber($subscriber);
        }

        if ($this->option('update')) {
            return $this->updateSubscriber($subscriber);
        }

        // Default: show subscriber details
        return $this->showSubscriberDetails($subscriber);
    }

    /**
     * Show subscriber details.
     */
    private function showSubscriberDetails(DistributionListSubscriber $subscriber): int
    {
        $this->info('👤 Subscriber Details:');
        $this->table(
            ['Field', 'Value'],
            [
                ['Email', $subscriber->email],
                ['Name', $subscriber->getFullName() ?: 'Not provided'],
                ['First Name', $subscriber->first_name ?: 'Not provided'],
                ['Last Name', $subscriber->last_name ?: 'Not provided'],
                ['Status', $subscriber->is_active ? 'Active' : 'Inactive'],
                ['Subscribed', $subscriber->created_at->format('Y-m-d H:i:s')],
                ['Last Updated', $subscriber->updated_at->format('Y-m-d H:i:s')],
            ]
        );

        if ($subscriber->metadata) {
            $this->newLine();
            $this->info('📝 Metadata:');
            foreach ($subscriber->metadata as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Activate a subscriber.
     */
    private function activateSubscriber(DistributionListSubscriber $subscriber): int
    {
        if ($subscriber->is_active) {
            $this->info("ℹ️  Subscriber '{$subscriber->email}' is already active.");

            return Command::SUCCESS;
        }

        try {
            $subscriber->update(['is_active' => true]);
            $this->info("✅ Subscriber '{$subscriber->email}' activated successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to activate subscriber: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Deactivate a subscriber.
     */
    private function deactivateSubscriber(DistributionListSubscriber $subscriber): int
    {
        if (! $subscriber->is_active) {
            $this->info("ℹ️  Subscriber '{$subscriber->email}' is already inactive.");

            return Command::SUCCESS;
        }

        try {
            $subscriber->update(['is_active' => false]);
            $this->info("✅ Subscriber '{$subscriber->email}' deactivated successfully.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to deactivate subscriber: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Update subscriber information.
     */
    private function updateSubscriber(DistributionListSubscriber $subscriber): int
    {
        $this->info("📝 Updating subscriber: {$subscriber->email}");

        $firstName = $this->ask('First name', $subscriber->first_name);
        $lastName = $this->ask('Last name', $subscriber->last_name);
        $isActive = $this->choice(
            'Status',
            ['Active', 'Inactive'],
            $subscriber->is_active ? 0 : 1
        );

        try {
            $subscriber->update([
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'is_active' => $isActive === 'Active',
            ]);

            $this->info('✅ Subscriber updated successfully.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to update subscriber: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Export subscribers to CSV.
     */
    private function exportSubscribers(DistributionList $list): int
    {
        $status = $this->option('status');
        $query = $list->subscribers();

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $subscribers = $query->orderBy('email')->get();

        if ($subscribers->isEmpty()) {
            $this->info('No subscribers to export.');

            return Command::SUCCESS;
        }

        $filename = "subscribers_{$list->slug}_".date('Y-m-d_H-i-s').'.csv';
        $filepath = storage_path("app/{$filename}");

        $handle = fopen($filepath, 'w');
        if ($handle === false) {
            $this->error('❌ Could not create export file.');

            return Command::FAILURE;
        }

        // Write header
        fputcsv($handle, ['Email', 'First Name', 'Last Name', 'Status', 'Subscribed', 'Last Updated']);

        // Write data
        foreach ($subscribers as $subscriber) {
            fputcsv($handle, [
                $subscriber->email,
                $subscriber->first_name ?: '',
                $subscriber->last_name ?: '',
                $subscriber->is_active ? 'Active' : 'Inactive',
                $subscriber->created_at->format('Y-m-d H:i:s'),
                $subscriber->updated_at->format('Y-m-d H:i:s'),
            ]);
        }

        fclose($handle);

        $this->info("✅ Exported {$subscribers->count()} subscribers to: {$filepath}");

        return Command::SUCCESS;
    }

    /**
     * Search subscribers.
     */
    private function searchSubscribers(DistributionList $list, string $search): int
    {
        $subscribers = $list->subscribers()
            ->where(function ($query) use ($search) {
                $query->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->orderBy('email')
            ->get();

        if ($subscribers->isEmpty()) {
            $this->info("No subscribers found matching '{$search}'.");

            return Command::SUCCESS;
        }

        $this->info('Found '.$subscribers->count()." subscribers matching '{$search}':");

        $rows = [];
        foreach ($subscribers as $subscriber) {
            $rows[] = [
                $subscriber->email,
                $subscriber->getFullName() ?: 'Not provided',
                $subscriber->is_active ? 'Active' : 'Inactive',
                $subscriber->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table(
            ['Email', 'Name', 'Status', 'Subscribed'],
            $rows
        );

        return Command::SUCCESS;
    }

    /**
     * List all subscribers.
     */
    private function listSubscribers(DistributionList $list): int
    {
        $status = $this->option('status');
        $query = $list->subscribers();

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $subscribers = $query->orderBy('email')->get();

        if ($subscribers->isEmpty()) {
            $this->info('No subscribers found.');

            return Command::SUCCESS;
        }

        $this->info("Subscribers in '{$list->name}' (".$subscribers->count().' total):');

        $rows = [];
        foreach ($subscribers as $subscriber) {
            $rows[] = [
                $subscriber->email,
                $subscriber->getFullName() ?: 'Not provided',
                $subscriber->is_active ? 'Active' : 'Inactive',
                $subscriber->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table(
            ['Email', 'Name', 'Status', 'Subscribed'],
            $rows
        );

        return Command::SUCCESS;
    }
}
