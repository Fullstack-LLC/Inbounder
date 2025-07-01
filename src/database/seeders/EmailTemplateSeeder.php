<?php

declare(strict_types=1);

namespace Inbounder\Database\Seeders;

use Illuminate\Database\Seeder;
use Inbounder\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Newsletter Template',
                'slug' => 'newsletter',
                'subject' => '{{subject}} - Newsletter',
                'html_content' => '<h1>{{subject}}</h1><p>Hello {{first_name}},</p><p>Welcome to our newsletter!</p><p>Best regards,<br>{{app_name}} Team</p>',
                'text_content' => '{{subject}}\n\nHello {{first_name}},\n\nWelcome to our newsletter!\n\nBest regards,\n{{app_name}} Team',
                'category' => 'newsletter',
                'is_active' => true,
                'variables' => ['subject', 'first_name', 'app_name'],
            ],
            [
                'name' => 'Welcome Email',
                'slug' => 'welcome',
                'subject' => 'Welcome to {{app_name}}, {{first_name}}!',
                'html_content' => '<h1>Welcome {{first_name}}!</h1><p>Thank you for joining {{app_name}}.</p><p>We\'re excited to have you on board!</p><p>Best regards,<br>{{app_name}} Team</p>',
                'text_content' => 'Welcome {{first_name}}!\n\nThank you for joining {{app_name}}.\n\nWe\'re excited to have you on board!\n\nBest regards,\n{{app_name}} Team',
                'category' => 'onboarding',
                'is_active' => true,
                'variables' => ['first_name', 'app_name'],
            ],
            [
                'name' => 'Campaign Email',
                'slug' => 'campaign',
                'subject' => '{{campaign_subject}}',
                'html_content' => '<h1>{{campaign_title}}</h1><p>{{campaign_content}}</p><p>Best regards,<br>{{app_name}} Team</p>',
                'text_content' => '{{campaign_title}}\n\n{{campaign_content}}\n\nBest regards,\n{{app_name}} Team',
                'category' => 'marketing',
                'is_active' => true,
                'variables' => ['campaign_subject', 'campaign_title', 'campaign_content', 'app_name'],
            ],
            [
                'name' => 'Notification Email',
                'slug' => 'notification',
                'subject' => '{{subject}}',
                'html_content' => '<h2>{{subject}}</h2><p>{{message}}</p><p>Best regards,<br>{{app_name}} Team</p>',
                'text_content' => '{{subject}}\n\n{{message}}\n\nBest regards,\n{{app_name}} Team',
                'category' => 'notification',
                'is_active' => true,
                'variables' => ['subject', 'message', 'app_name'],
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
