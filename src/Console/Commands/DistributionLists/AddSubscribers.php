<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\DistributionLists;

use Illuminate\Console\Command;
use Inbounder\Models\DistributionList;
use Inbounder\Services\DistributionListService;

/**
 * Command to add subscribers to distribution lists.
 */
class AddSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:add-subscribers
                            {list : List slug}
                            {--email=* : Email addresses to add}
                            {--file= : CSV file with subscribers (email)}
                            {--interactive : Add subscribers interactively}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add subscribers to a distribution list';

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

        $this->info("📧 Adding subscribers to list: {$list->name}");

        if ($this->option('interactive')) {
            return $this->addInteractive($list);
        }

        if ($file = $this->option('file')) {
            return $this->addFromFile($list, $file);
        }

        if ($emails = $this->option('email')) {
            return $this->addEmails($list, $emails);
        }

        $this->error('❌ Please specify --email, --file, or --interactive option.');

        return Command::FAILURE;
    }

    /**
     * Add subscribers interactively.
     */
    private function addInteractive(DistributionList $list): int
    {
        $subscribers = [];

        $this->info('Enter subscriber details (press Enter twice to finish):');

        while (true) {
            $email = $this->ask('Email address');
            if (empty($email)) {
                break;
            }

            $subscribers[] = [
                'email' => $email,
            ];
        }

        if (empty($subscribers)) {
            $this->info('No subscribers to add.');

            return Command::SUCCESS;
        }

        return $this->processSubscribers($list, $subscribers);
    }

    /**
     * Add subscribers from CSV file.
     */
    private function addFromFile(DistributionList $list, string $filePath): int
    {
        if (! file_exists($filePath)) {
            $this->error("❌ File '{$filePath}' not found.");

            return Command::FAILURE;
        }

        $subscribers = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            $this->error("❌ Could not open file '{$filePath}'.");

            return Command::FAILURE;
        }

        // Skip header row
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) >= 1 && ! empty($data[0])) {
                $subscribers[] = [
                    'email' => trim($data[0]),
                ];
            }
        }

        fclose($handle);

        if (empty($subscribers)) {
            $this->info('No valid subscribers found in file.');

            return Command::SUCCESS;
        }

        $this->info('Found '.count($subscribers).' subscribers in file.');

        return $this->processSubscribers($list, $subscribers);
    }

    /**
     * Add individual email addresses.
     */
    private function addEmails(DistributionList $list, array $emails): int
    {
        $subscribers = [];
        foreach ($emails as $email) {
            $subscribers[] = [
                'email' => $email,
            ];
        }

        return $this->processSubscribers($list, $subscribers);
    }

    /**
     * Process subscribers and add them to the list.
     */
    private function processSubscribers(DistributionList $list, array $subscribers): int
    {
        try {
            $results = $this->listService->addSubscribers($list, $subscribers);

            $this->info('✅ Subscribers processed:');
            $this->line("  Added: {$results['added']}");
            $this->line("  Updated: {$results['updated']}");

            if (! empty($results['errors'])) {
                $this->warn('⚠️  Errors encountered:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - {$error['email']}: {$error['error']}");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to add subscribers: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
