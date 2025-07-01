<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\Demo;

use Illuminate\Console\Command;
use Inbounder\Database\Seeders\EmailTemplateSeeder;

/**
 * Command to set up default email templates.
 */
class SetupDefaultTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:setup-templates
                            {--force : Force recreation of existing templates}
                            {--list : List created templates after setup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up default email templates for testing and development';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📧 Setting up default email templates...');

        try {
            $seeder = new EmailTemplateSeeder;
            $seeder->run();

            $this->info('✅ Default templates created successfully!');

            if ($this->option('list')) {
                $this->newLine();
                $this->call('mailgun:list-templates', ['--active' => true]);
            }

            $this->newLine();
            $this->info('📝 Available templates:');
            $this->line('  • newsletter - Newsletter template with variables: name, first_name, last_name, subject, content, unsubscribe_url');
            $this->line('  • welcome - Welcome email template with variables: name, first_name, last_name, app_name, login_url');
            $this->line('  • notification - Notification template with variables: name, first_name, last_name, subject, message, action_url');
            $this->line('  • campaign - Marketing campaign template with variables: name, first_name, last_name, campaign_subject, campaign_content, cta_text, cta_url');

            $this->newLine();
            $this->info('💡 You can now use these templates with the mock campaign:');
            $this->line('  php artisan mailgun:mock-campaign --template=newsletter --users=10');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to set up templates: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
