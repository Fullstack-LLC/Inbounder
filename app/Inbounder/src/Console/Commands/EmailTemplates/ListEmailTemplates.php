<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\EmailTemplates;

use Illuminate\Console\Command;
use Inbounder\Models\EmailTemplate;
use Inbounder\Services\EmailTemplateService;

/**
 * Command to list and manage email templates.
 */
class ListEmailTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:list-templates
                            {--category= : Filter by category}
                            {--active : Show only active templates}
                            {--inactive : Show only inactive templates}
                            {--stats : Show template statistics}
                            {--show-variables : Show template variables}
                            {--template= : Show details for specific template}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List and manage email templates';

    /**
     * The email template service instance.
     */
    private EmailTemplateService $templateService;

    /**
     * Create a new command instance.
     */
    public function __construct(EmailTemplateService $templateService)
    {
        parent::__construct();
        $this->templateService = $templateService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->showStats();
        }

        if ($templateSlug = $this->option('template')) {
            return $this->showTemplateDetails($templateSlug);
        }

        return $this->listTemplates();
    }

    /**
     * Show template statistics.
     */
    private function showStats(): int
    {
        $stats = $this->templateService->getTemplateStats();

        $this->info('📊 Email Template Statistics:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Templates', $stats['total']],
                ['Active Templates', $stats['active']],
                ['Inactive Templates', $stats['inactive']],
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
     * Show details for a specific template.
     */
    private function showTemplateDetails(string $templateSlug): int
    {
        $template = EmailTemplate::where('slug', $templateSlug)->first();

        if (! $template) {
            $this->error("❌ Template '{$templateSlug}' not found.");

            return Command::FAILURE;
        }

        $this->info("📧 Template: {$template->name}");
        $this->line("Slug: {$template->slug}");
        $this->line("Subject: {$template->subject}");
        $this->line('Category: '.($template->category ?: 'None'));
        $this->line('Status: '.($template->is_active ? 'Active' : 'Inactive'));
        $this->line("Created: {$template->created_at}");
        $this->line("Updated: {$template->updated_at}");

        if ($this->option('show-variables') || ! empty($template->getAvailableVariables())) {
            $this->newLine();
            $this->info('🔤 Variables:');
            $variables = $template->getAvailableVariables();
            if (empty($variables)) {
                $this->line('  No variables found');
            } else {
                foreach ($variables as $variable) {
                    $this->line("  • {{$variable}}");
                }
            }
        }

        $this->newLine();
        $this->info('📝 HTML Content Preview:');
        $this->line(substr($template->html_content, 0, 200).(strlen($template->html_content) > 200 ? '...' : ''));

        if ($template->text_content) {
            $this->newLine();
            $this->info('📄 Text Content Preview:');
            $this->line(substr($template->text_content, 0, 200).(strlen($template->text_content) > 200 ? '...' : ''));
        }

        return Command::SUCCESS;
    }

    /**
     * List templates.
     */
    private function listTemplates(): int
    {
        $query = EmailTemplate::query();

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

        $templates = $query->orderBy('name')->get();

        if ($templates->isEmpty()) {
            $this->info('📭 No templates found.');

            return Command::SUCCESS;
        }

        $this->info('📧 Email Templates:');

        $rows = [];
        foreach ($templates as $template) {
            $row = [
                $template->name,
                $template->slug,
                $template->subject,
                $template->category ?: 'None',
                $template->is_active ? 'Active' : 'Inactive',
                $template->created_at->format('Y-m-d H:i'),
            ];

            if ($this->option('show-variables')) {
                $variables = $template->getAvailableVariables();
                $row[] = ! empty($variables) ? implode(', ', $variables) : 'None';
            }

            $rows[] = $row;
        }

        $headers = ['Name', 'Slug', 'Subject', 'Category', 'Status', 'Created'];
        if ($this->option('show-variables')) {
            $headers[] = 'Variables';
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
