<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\DistributionLists;

use Illuminate\Console\Command;
use Inbounder\Models\DistributionList;
use Inbounder\Services\DistributionListService;

/**
 * Command to list and manage distribution lists.
 */
class ListDistributionLists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:list-lists
                            {--category= : Filter by category}
                            {--active : Show only active lists}
                            {--inactive : Show only inactive lists}
                            {--stats : Show list statistics}
                            {--show-subscribers : Show subscriber counts}
                            {--list= : Show details for specific list}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List and manage distribution lists';

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
        if ($this->option('stats')) {
            return $this->showStats();
        }

        if ($listSlug = $this->option('list')) {
            return $this->showListDetails($listSlug);
        }

        return $this->listLists();
    }

    /**
     * Show list statistics.
     */
    private function showStats(): int
    {
        $stats = $this->listService->getListStats();

        $this->info('📊 Distribution List Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Lists', $stats['total_lists']],
                ['Active Lists', $stats['active_lists']],
                ['Inactive Lists', $stats['inactive_lists']],
                ['Categories', $stats['categories']],
            ]
        );

        if (! empty($stats['category_list'])) {
            $this->info('📂 Categories:');
            foreach ($stats['category_list'] as $category) {
                $this->line("  • {$category}");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Show details for a specific list.
     */
    private function showListDetails(string $listSlug): int
    {
        $list = DistributionList::where('slug', $listSlug)->first();

        if (! $list) {
            $this->error("❌ List '{$listSlug}' not found.");

            return Command::FAILURE;
        }

        $stats = $list->getStats();

        $this->info("📧 List: {$list->name}");
        $this->line("Slug: {$list->slug}");
        $this->line('Description: '.($list->description ?: 'None'));
        $this->line('Category: '.($list->category ?: 'None'));
        $this->line('Status: '.($list->is_active ? 'Active' : 'Inactive'));
        $this->line("Created: {$list->created_at}");
        $this->line("Updated: {$list->updated_at}");

        $this->newLine();
        $this->info('📊 Subscriber Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Subscribers', $stats['total_subscribers']],
                ['Active Subscribers', $stats['active_subscribers']],
                ['Inactive Subscribers', $stats['inactive_subscribers']],
                ['Subscription Rate', $stats['subscription_rate'].'%'],
            ]
        );

        if ($this->option('show-subscribers')) {
            $this->newLine();
            $this->info('👥 Subscribers:');
            $subscribers = $list->subscribers()->orderBy('email')->get();

            if ($subscribers->isEmpty()) {
                $this->line('  No subscribers found');
            } else {
                $rows = [];
                foreach ($subscribers as $subscriber) {
                    $rows[] = [
                        $subscriber->email,
                        $subscriber->getFullName(),
                        $subscriber->is_active ? 'Active' : 'Inactive',
                        $subscriber->created_at->format('Y-m-d H:i'),
                    ];
                }

                $this->table(
                    ['Email', 'Name', 'Status', 'Subscribed'],
                    $rows
                );
            }
        }

        return Command::SUCCESS;
    }

    /**
     * List distribution lists.
     */
    private function listLists(): int
    {
        $query = DistributionList::query();

        // Apply filters
        if ($category = $this->option('category')) {
            $query->byCategory($category);
        }

        if ($this->option('active')) {
            $query->active();
        }

        if ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $lists = $query->orderBy('name')->get();

        if ($lists->isEmpty()) {
            $this->info('📭 No lists found.');

            return Command::SUCCESS;
        }

        $this->info('📧 Distribution Lists:');

        $rows = [];
        foreach ($lists as $list) {
            $row = [
                $list->name,
                $list->slug,
                $list->category ?: 'None',
                $list->is_active ? 'Active' : 'Inactive',
                $list->getSubscriberCount(),
                $list->created_at->format('Y-m-d H:i'),
            ];

            if ($this->option('show-subscribers')) {
                $row[] = $list->getTotalSubscriberCount();
            }

            $rows[] = $row;
        }

        $headers = ['Name', 'Slug', 'Category', 'Status', 'Active Subscribers', 'Created'];
        if ($this->option('show-subscribers')) {
            $headers[] = 'Total Subscribers';
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
