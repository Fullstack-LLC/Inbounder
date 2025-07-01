<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\DistributionLists;

use Illuminate\Console\Command;
use Inbounder\Services\DistributionListService;

/**
 * Command to create distribution lists interactively.
 */
class CreateDistributionList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:create-list
                            {--name= : List name}
                            {--slug= : Custom slug}
                            {--description= : List description}
                            {--category= : List category}
                            {--inactive : Create as inactive}
                            {--metadata= : List metadata as JSON}
                            {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new distribution list';

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
        $this->info('📧 Creating new distribution list...');

        if ($this->option('interactive')) {
            return $this->runInteractive();
        }

        return $this->runNonInteractive();
    }

    /**
     * Run the command in interactive mode.
     */
    private function runInteractive(): int
    {
        $name = $this->ask('List name');
        $description = $this->ask('List description (optional)');
        $category = $this->ask('List category (optional)');
        $isActive = $this->confirm('Should the list be active?', true);

        try {
            $list = $this->listService->createList([
                'name' => $name,
                'description' => $description ?: null,
                'category' => $category ?: null,
                'is_active' => $isActive,
                'metadata' => null,
            ]);
            $this->info('✅ Distribution list created successfully!');
            $this->info("Name: {$list->name}");
            $this->info("Slug: {$list->slug}");
            $this->info('Description: '.($list->description ?: 'None'));
            $this->info('Category: '.($list->category ?: 'None'));
            $this->info('Status: '.($list->is_active ? 'Active' : 'Inactive'));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create distribution list: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Run the command in non-interactive mode.
     */
    private function runNonInteractive(): int
    {
        $name = $this->option('name');
        $slug = $this->option('slug');
        $description = $this->option('description');
        $category = $this->option('category');
        $isActive = ! $this->option('inactive');
        $metadata = $this->option('metadata');
        $metadataArr = null;
        if ($metadata) {
            try {
                $metadataArr = json_decode($metadata, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->error('❌ Invalid JSON for metadata.');

                return Command::FAILURE;
            }
        }
        if (! $name) {
            $this->error('❌ List name is required when not in interactive mode.');

            return Command::FAILURE;
        }
        try {
            $listData = [
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'is_active' => $isActive,
                'metadata' => $metadataArr,
            ];

            // Only add slug if explicitly provided
            if ($slug) {
                $listData['slug'] = $slug;
            }

            $list = $this->listService->createList($listData);
            $this->info('✅ Distribution list created successfully!');
            $this->info("Name: {$list->name}");
            $this->info("Slug: {$list->slug}");
            $this->info('Description: '.($list->description ?: 'None'));
            $this->info('Category: '.($list->category ?: 'None'));
            $this->info('Status: '.($list->is_active ? 'Active' : 'Inactive'));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create distribution list: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Determine if the command should run in interactive mode.
     */
    private function shouldRunInteractive(): bool
    {
        return $this->option('interactive');
    }
}
