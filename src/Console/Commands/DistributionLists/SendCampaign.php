<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\DistributionLists;

use Illuminate\Console\Command;
use Inbounder\Models\DistributionList;
use Inbounder\Models\EmailTemplate;
use Inbounder\Services\DistributionListService;
use Inbounder\Services\EmailTemplateService;

/**
 * Command to send email campaigns to distribution lists.
 */
class SendCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:send-campaign
                            {list : List slug}
                            {template : Template slug}
                            {--subject= : Custom subject line}
                            {--from= : From email address}
                            {--from-name= : From name}
                            {--variables=* : Template variables (key=value)}
                            {--dry-run : Show what would be sent without actually sending}
                            {--limit= : Limit number of emails to send}
                            {--delay= : Delay between emails in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email campaign to a distribution list';

    /**
     * The distribution list service instance.
     */
    private DistributionListService $listService;

    /**
     * The email template service instance.
     */
    private EmailTemplateService $templateService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        DistributionListService $listService,
        EmailTemplateService $templateService
    ) {
        parent::__construct();
        $this->listService = $listService;
        $this->templateService = $templateService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $listSlug = $this->argument('list');
        $templateSlug = $this->argument('template');

        // Get the distribution list
        $list = $this->listService->getListBySlug($listSlug);
        if (! $list) {
            $this->error("❌ List '{$listSlug}' not found or inactive.");

            return Command::FAILURE;
        }

        // Get the email template
        $template = $this->templateService->getTemplateBySlug($templateSlug);
        if (! $template) {
            $this->error("❌ Template '{$templateSlug}' not found.");

            return Command::FAILURE;
        }

        $this->info("📧 Sending campaign to list: {$list->name}");
        $this->line("Template: {$template->name}");

        // Parse variables
        $variables = $this->parseVariables();
        if ($variables === false) {
            return Command::FAILURE;
        }

        // Prepare campaign options
        $options = [
            'subject' => $this->option('subject'),
            'from' => $this->option('from'),
            'from_name' => $this->option('from-name'),
            'dry_run' => $this->option('dry-run'),
            'limit' => $this->option('limit') ? (int) $this->option('limit') : null,
            'delay' => $this->option('delay') ? (int) $this->option('delay') : null,
        ];

        // Show campaign summary
        $this->showCampaignSummary($list, $template, $variables, $options);

        if ($this->option('dry-run')) {
            $this->info('✅ Dry run completed. No emails were sent.');

            return Command::SUCCESS;
        }

        // Confirm before sending
        if (! $this->confirm('Are you sure you want to send this campaign?')) {
            $this->info('Campaign cancelled.');

            return Command::SUCCESS;
        }

        // Send the campaign
        return $this->sendCampaign($list, $template, $variables, $options);
    }

    /**
     * Parse template variables from command options.
     *
     * @return array|false
     */
    private function parseVariables()
    {
        $variables = [];
        $variableOptions = $this->option('variables');

        foreach ($variableOptions as $variable) {
            if (strpos($variable, '=') === false) {
                $this->error("❌ Invalid variable format: {$variable}. Use key=value format.");

                return false;
            }

            [$key, $value] = explode('=', $variable, 2);
            $variables[trim($key)] = trim($value);
        }

        return $variables;
    }

    /**
     * Show campaign summary before sending.
     */
    private function showCampaignSummary(
        DistributionList $list,
        EmailTemplate $template,
        array $variables,
        array $options
    ): void {
        $subscriberCount = $list->getSubscriberCount();
        $limit = $options['limit'];

        $this->newLine();
        $this->info('📊 Campaign Summary:');
        $this->table(
            ['Setting', 'Value'],
            [
                ['List', $list->name],
                ['Template', $template->name],
                ['Subscribers', $limit ? min($subscriberCount, $limit) : $subscriberCount],
                ['Subject', $options['subject'] ?: $template->subject],
                ['From', $options['from'] ?: config('mailgun.from_address')],
                ['Dry Run', $options['dry_run'] ? 'Yes' : 'No'],
            ]
        );

        if (! empty($variables)) {
            $this->newLine();
            $this->info('📝 Template Variables:');
            $varRows = [];
            foreach ($variables as $key => $value) {
                $varRows[] = [$key, $value];
            }
            $this->table(['Variable', 'Value'], $varRows);
        }

        if ($options['delay']) {
            $this->newLine();
            $this->warn("⚠️  Delay between emails: {$options['delay']} seconds");
        }
    }

    /**
     * Send the campaign.
     */
    private function sendCampaign(
        DistributionList $list,
        EmailTemplate $template,
        array $variables,
        array $options
    ): int {
        try {
            $this->info('🚀 Sending campaign...');

            $results = $this->listService->sendCampaignToList(
                $list,
                $template->slug,
                $variables,
                $options
            );

            $this->newLine();
            $this->info('✅ Campaign completed:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Subscribers', $results['total_subscribers']],
                    ['Emails Sent', $results['emails_sent']],
                    ['Emails Failed', $results['emails_failed']],
                    ['Emails Skipped', $results['emails_skipped']],
                ]
            );

            if (! empty($results['errors'])) {
                $this->warn('⚠️  Errors encountered:');
                foreach (array_slice($results['errors'], 0, 10) as $error) {
                    $this->line("  - {$error['email']}: {$error['error']}");
                }

                if (count($results['errors']) > 10) {
                    $this->line('  ... and '.(count($results['errors']) - 10).' more errors');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send campaign: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
