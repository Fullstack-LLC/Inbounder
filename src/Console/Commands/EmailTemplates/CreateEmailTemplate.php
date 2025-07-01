<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\EmailTemplates;

use Illuminate\Console\Command;
use Inbounder\Services\EmailTemplateService;

/**
 * Command to create email templates interactively.
 */
class CreateEmailTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:create-template
                            {--name= : Template name}
                            {--subject= : Email subject}
                            {--html= : HTML content}
                            {--text= : Text content}
                            {--category= : Template category}
                            {--interactive : Run in interactive mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new email template';

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
        $this->info('📧 Creating new email template...');

        if ($this->option('interactive') || $this->shouldRunInteractive()) {
            return $this->runInteractive();
        }

        return $this->runNonInteractive();
    }

    /**
     * Run the command in interactive mode.
     */
    private function runInteractive(): int
    {
        $name = $this->ask('Template name');
        $subject = $this->ask('Email subject');
        $category = $this->ask('Category (optional)');

        $this->info('Enter HTML content (press Enter twice to finish):');
        $htmlContent = $this->getMultilineInput();

        $this->info('Enter text content (optional, press Enter twice to finish):');
        $textContent = $this->getMultilineInput();

        try {
            $template = $this->templateService->createTemplate([
                'name' => $name,
                'subject' => $subject,
                'html_content' => $htmlContent,
                'text_content' => $textContent ?: null,
                'category' => $category ?: null,
            ]);

            $this->info("✅ Template '{$template->name}' created successfully!");
            $this->info("Slug: {$template->slug}");
            $this->info('Variables: '.implode(', ', $template->getAvailableVariables()));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create template: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Run the command in non-interactive mode.
     */
    private function runNonInteractive(): int
    {
        $name = $this->option('name');
        $subject = $this->option('subject');
        $htmlContent = $this->option('html');
        $textContent = $this->option('text');
        $category = $this->option('category');

        if (! $name || ! $subject || ! $htmlContent) {
            $this->error('❌ Name, subject, and HTML content are required when not in interactive mode.');

            return Command::FAILURE;
        }

        try {
            $template = $this->templateService->createTemplate([
                'name' => $name,
                'subject' => $subject,
                'html_content' => $htmlContent,
                'text_content' => $textContent,
                'category' => $category,
            ]);

            $this->info("✅ Template '{$template->name}' created successfully!");
            $this->info("Slug: {$template->slug}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create template: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Determine if the command should run in interactive mode.
     */
    private function shouldRunInteractive(): bool
    {
        return ! $this->option('name') && ! $this->option('subject') && ! $this->option('html');
    }

    /**
     * Get multiline input from the user.
     */
    private function getMultilineInput(): string
    {
        $lines = [];
        $lineCount = 0;

        while (true) {
            $line = $this->ask('Line '.($lineCount + 1));

            if (empty($line) && $lineCount > 0) {
                break;
            }

            $lines[] = $line;
            $lineCount++;
        }

        return implode("\n", $lines);
    }
}
