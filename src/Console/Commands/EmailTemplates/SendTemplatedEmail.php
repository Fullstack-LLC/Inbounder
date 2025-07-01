<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\EmailTemplates;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Inbounder\Mail\TemplatedEmail;
use Inbounder\Services\EmailTemplateService;

/**
 * Command to send emails using templates.
 */
class SendTemplatedEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:send-template
                            {template : Template slug}
                            {recipient : Recipient email address}
                            {--variables=* : Template variables (key=value format)}
                            {--from= : From email address}
                            {--from-name= : From name}
                            {--preview : Preview the email without sending}
                            {--dry-run : Show what would be sent without actually sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send an email using a template';

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
        $templateSlug = $this->argument('template');
        $recipient = $this->argument('recipient');
        $variables = $this->parseVariables();
        $preview = $this->option('preview');
        $dryRun = $this->option('dry-run');

        try {
            // Get template and render it
            $rendered = $this->templateService->renderTemplate($templateSlug, $variables);

            if ($preview || $dryRun) {
                return $this->previewEmail($rendered, $recipient, $variables);
            }

            return $this->sendEmail($templateSlug, $recipient, $variables);

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Preview the email.
     */
    private function previewEmail(array $rendered, string $recipient, array $variables): int
    {
        $this->info('📧 Email Preview:');
        $this->line("To: {$recipient}");
        $this->line("Subject: {$rendered['subject']}");
        $this->line("Template: {$rendered['template']->name}");

        if (! empty($variables)) {
            $this->newLine();
            $this->info('🔤 Variables:');
            foreach ($variables as $key => $value) {
                $this->line("  {$key}: {$value}");
            }
        }

        $this->newLine();
        $this->info('📝 HTML Content:');
        $this->line($rendered['html_content']);

        if ($rendered['text_content']) {
            $this->newLine();
            $this->info('📄 Text Content:');
            $this->line($rendered['text_content']);
        }

        return Command::SUCCESS;
    }

    /**
     * Send the email.
     */
    private function sendEmail(string $templateSlug, string $recipient, array $variables): int
    {
        $options = [];

        if ($from = $this->option('from')) {
            $options['from'] = [
                'address' => $from,
                'name' => $this->option('from-name'),
            ];
        }

        $mailable = new TemplatedEmail($templateSlug, $variables, $options);

        try {
            Mail::to($recipient)->send($mailable);

            $this->info("✅ Email sent successfully to {$recipient}");
            $this->line("Template: {$templateSlug}");
            $this->line("Subject: {$mailable->subject}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Parse variables from command options.
     */
    private function parseVariables(): array
    {
        $variables = [];
        $variableOptions = $this->option('variables');

        foreach ($variableOptions as $variable) {
            if (strpos($variable, '=') !== false) {
                [$key, $value] = explode('=', $variable, 2);
                $variables[trim($key)] = trim($value);
            }
        }

        return $variables;
    }
}
