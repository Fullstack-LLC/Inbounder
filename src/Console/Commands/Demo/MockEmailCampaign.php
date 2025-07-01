<?php

declare(strict_types=1);

namespace Inbounder\Console\Commands\Demo;

use Illuminate\Support\Facades\Config;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Inbounder\Models\DistributionList;
use Inbounder\Models\MailgunEvent;
use Inbounder\Services\DistributionListService;
use Inbounder\Services\MailgunTrackingService;
use Inbounder\Services\TemplatedEmailJobDispatcher;

/**
 * Mock email campaign command for testing and demonstration.
 *
 * This command simulates sending emails to multiple users and generates
 * realistic webhook events without actually contacting Mailgun.
 */
class MockEmailCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:mock-campaign
                            {--users=500 : Number of users to use from the users table}
                            {--emails=1 : Number of emails per user}
                            {--campaign=test-campaign : Campaign ID}
                            {--template=newsletter : Template name}
                            {--subject=Test Newsletter : Email subject}
                            {--batch-size=50 : Number of emails to process per batch}
                            {--delay=0 : Delay between batches in milliseconds}
                            {--loop : Continuously send emails every 5 seconds}
                            {--loop-interval=5 : Interval between loop iterations in seconds}
                            {--delivery-rate=95 : Percentage of emails that get delivered}
                            {--open-rate=45 : Percentage of delivered emails that get opened}
                            {--click-rate=12 : Percentage of opened emails that get clicked}
                            {--bounce-rate=3 : Percentage of emails that bounce}
                            {--complain-rate=1 : Percentage of emails that get complained}
                            {--unsubscribe-rate=2 : Percentage of emails that get unsubscribed}
                            {--list-name=Mock Campaign List : Name for the distribution list}
                            {--list-slug= : Custom slug for the distribution list}
                            {--list-category=Demo : Category for the distribution list}
                            {--create-list : Create a new distribution list for this campaign}
                            {--use-existing-list= : Use existing distribution list by slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mock an email campaign with webhook simulation for testing';

    /**
     * The tracking service instance.
     */
    private MailgunTrackingService $trackingService;

    /**
     * The email job dispatcher instance.
     */
    private TemplatedEmailJobDispatcher $emailDispatcher;

    /**
     * The distribution list service instance.
     */
    private DistributionListService $listService;

    /**
     * Create a new command instance.
     */
    public function __construct(
        MailgunTrackingService $trackingService,
        TemplatedEmailJobDispatcher $emailDispatcher,
        DistributionListService $listService
    ) {
        parent::__construct();
        $this->trackingService = $trackingService;
        $this->emailDispatcher = $emailDispatcher;
        $this->listService = $listService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting mock email campaign simulation...');
        $this->info('📧 Note: Make sure your queue worker is running: php artisan queue:work');
        $this->newLine();

        $numUsers = (int) $this->option('users');
        $emailsPerUser = (int) $this->option('emails');
        $totalEmails = $numUsers * $emailsPerUser;
        $campaignId = $this->option('campaign');
        $templateName = $this->option('template');
        $subject = $this->option('subject');
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $loop = $this->option('loop');
        $loopInterval = (int) $this->option('loop-interval');
        $createList = $this->option('create-list');
        $useExistingList = $this->option('use-existing-list');

        // Event rates
        $deliveryRate = (int) $this->option('delivery-rate');
        $openRate = (int) $this->option('open-rate');
        $clickRate = (int) $this->option('click-rate');
        $bounceRate = (int) $this->option('bounce-rate');
        $complainRate = (int) $this->option('complain-rate');
        $unsubscribeRate = (int) $this->option('unsubscribe-rate');

        // If no template is provided, pick a random one from active templates
        if (! $templateName) {
            $templateName = $this->getRandomActiveTemplateSlug();
            $this->info("🎲 No template specified. Randomly selected template: {$templateName}");
        }

        // Validate template exists
        if (! $this->validateTemplate($templateName)) {
            $this->error("❌ Template '{$templateName}' not found or inactive.");
            $this->info('💡 Available templates:');
            $this->listAvailableTemplates();
            $this->info('💡 You can create templates using: php artisan mailgun:create-template --interactive');
            $this->info('💡 Or run the seeder to create default templates: php artisan mailgun:setup-templates');

            return Command::FAILURE;
        }

        // Get or create distribution list
        $list = $this->getOrCreateDistributionList($createList, $useExistingList);
        if (! $list) {
            return Command::FAILURE;
        }

        $this->info("📧 Campaign: {$campaignId}");
        $this->info("📝 Template: {$templateName}");
        $this->info("📋 Subject: {$subject}");
        $this->info("📧 Distribution List: {$list->name} ({$list->slug})");
        $this->info("👥 Users: {$numUsers}");
        $this->info("📨 Emails per user: {$emailsPerUser}");
        $this->info("📊 Total emails: {$totalEmails}");
        $this->info("📦 Batch size: {$batchSize}");
        $this->info("⏱️  Batch delay: {$delay}ms");
        if ($loop) {
            $this->info("🔄 Loop mode: Enabled (every {$loopInterval}s)");
        }
        $this->info("📊 Delivery Rate: {$deliveryRate}%");
        $this->info("👁️  Open Rate: {$openRate}%");
        $this->info("🔗 Click Rate: {$clickRate}%");

        // Select real users from the users table
        $users = $this->getRealUsers($numUsers);
        if (count($users) === 0) {
            $this->error('❌ No users found in the users table.');

            return Command::FAILURE;
        }
        $this->addUsersToDistributionList($list, $users);

        if ($loop) {
            return $this->runLoopMode($list, $users, $campaignId, $templateName, $subject, $loopInterval, $deliveryRate, $openRate, $clickRate, $bounceRate, $complainRate, $unsubscribeRate);
        }

        return $this->runSingleCampaign($list, $users, $campaignId, $templateName, $subject, $batchSize, $delay, $emailsPerUser, $deliveryRate, $openRate, $clickRate, $bounceRate, $complainRate, $unsubscribeRate);
    }

    /**
     * Get or create a distribution list.
     */
    private function getOrCreateDistributionList(bool $createList, ?string $useExistingList): ?DistributionList
    {
        // If using existing list
        if ($useExistingList) {
            $list = $this->listService->getListBySlug($useExistingList);
            if (! $list) {
                $this->error("❌ Distribution list '{$useExistingList}' not found or inactive.");

                return null;
            }
            $this->info("✅ Using existing distribution list: {$list->name}");

            return $list;
        }

        // If creating new list
        if ($createList) {
            $listName = $this->option('list-name');
            $listSlug = $this->option('list-slug');
            $listCategory = $this->option('list-category');

            try {
                $listData = [
                    'name' => $listName,
                    'description' => "Mock campaign list created for testing - {$listName}",
                    'category' => $listCategory,
                    'is_active' => true,
                    'metadata' => [
                        'mock_campaign' => true,
                        'created_at' => now()->toISOString(),
                    ],
                ];

                if ($listSlug) {
                    $listData['slug'] = $listSlug;
                }

                $list = $this->listService->createList($listData);
                $this->info("✅ Created new distribution list: {$list->name} ({$list->slug})");

                return $list;
            } catch (\Exception $e) {
                $this->error('❌ Failed to create distribution list: '.$e->getMessage());

                return null;
            }
        }

        // Default: create a temporary list for this campaign
        $listName = $this->option('list-name').' - '.date('Y-m-d H:i:s');
        try {
            $list = $this->listService->createList([
                'name' => $listName,
                'description' => "Temporary mock campaign list - {$listName}",
                'category' => $this->option('list-category'),
                'is_active' => true,
                'metadata' => [
                    'mock_campaign' => true,
                    'temporary' => true,
                    'created_at' => now()->toISOString(),
                ],
            ]);
            $this->info("✅ Created temporary distribution list: {$list->name} ({$list->slug})");

            return $list;
        } catch (\Exception $e) {
            $this->error('❌ Failed to create distribution list: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Add users to the distribution list.
     */
    private function addUsersToDistributionList(DistributionList $list, array $users): void
    {
        $this->info('👥 Adding '.count($users).' users to distribution list...');

        $subscribers = [];
        foreach ($users as $user) {
            $subscribers[] = [
                'user_id' => $user['user_id'],
                'email' => $user['email'],
                'metadata' => [
                    'mock_user' => true,
                    'user_id' => $user['user_id'],
                    'domain' => $user['domain'],
                ],
            ];
        }

        $results = $this->listService->addSubscribers($list, $subscribers);

        $this->info("✅ Added {$results['added']} new subscribers");
        if ($results['updated'] > 0) {
            $this->info("🔄 Updated {$results['updated']} existing subscribers");
        }
        if (! empty($results['errors'])) {
            $this->warn('⚠️  '.count($results['errors']).' errors occurred while adding subscribers');
        }

        $this->info("📧 Total subscribers in list: {$list->getSubscriberCount()}");
    }

    /**
     * Run the campaign in loop mode.
     */
    private function runLoopMode(DistributionList $list, array $users, string $campaignId, string $templateName, string $subject, int $loopInterval, int $deliveryRate, int $openRate, int $clickRate, int $bounceRate, int $complainRate, int $unsubscribeRate): int
    {
        $this->info('🔄 Starting loop mode - Press Ctrl+C to stop');
        $this->newLine();

        // Set up signal handling for graceful shutdown
        $shouldStop = false;
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () use (&$shouldStop) {
                $shouldStop = true;
            });
        }

        $iteration = 0;
        $totalStats = [
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
            'complained' => 0,
            'unsubscribed' => 0,
        ];

        while (! $shouldStop) {
            $iteration++;
            $startTime = microtime(true);

            $this->info("📧 Loop iteration {$iteration} - ".date('Y-m-d H:i:s'));

            // Get a random subscriber from the distribution list
            $subscriber = $list->activeSubscribers()->inRandomOrder()->first();
            if (! $subscriber) {
                $this->warn('⚠️  No active subscribers found in list. Skipping iteration.');
                sleep($loopInterval);

                continue;
            }

            $messageId = $this->generateMessageId();
            $emailSubject = $this->generateEmailSubject($subject, $iteration, 1);

            // Prepare template variables based on template type
            $templateVariables = $this->prepareTemplateVariables($templateName, [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'full_name' => $subscriber->getFullName(),
            ], $campaignId, $emailSubject, $iteration);

            // Dispatch email job
            $this->emailDispatcher->sendToOne(
                $subscriber->email,
                $templateName,
                $templateVariables,
                [
                    'from' => [
                        'address' => 'noreply@example.com',
                        'name' => 'Test App',
                    ],
                    'subject' => $emailSubject,
                ]
            );

            // Create outbound email record for tracking
            $this->trackingService->createOutboundEmail($messageId, $subscriber->email, [
                'campaign_id' => $campaignId,
                'user_id' => $subscriber->id,
                'template_name' => $templateName,
                'subject' => $emailSubject,
                'from_address' => 'noreply@example.com',
                'from_name' => 'Test App',
                'metadata' => [
                    'mock_campaign' => true,
                    'loop_iteration' => $iteration,
                    'distribution_list_id' => $list->id,
                    'subscriber_data' => $subscriber->toArray(),
                ],
            ]);

            $totalStats['sent']++;

            // Simulate delivery
            if ($this->shouldHappen($deliveryRate)) {
                $this->simulateEvent($messageId, $subscriber->email, 'delivered');
                $totalStats['delivered']++;

                // Simulate opens
                if ($this->shouldHappen($openRate)) {
                    $this->simulateEvent($messageId, $subscriber->email, 'opened');
                    $totalStats['opened']++;

                    // Simulate clicks
                    if ($this->shouldHappen($clickRate)) {
                        $this->simulateEvent($messageId, $subscriber->email, 'clicked');
                        $totalStats['clicked']++;
                    }
                }

                // Simulate unsubscribes
                if ($this->shouldHappen($unsubscribeRate)) {
                    $this->simulateEvent($messageId, $subscriber->email, 'unsubscribed');
                    $totalStats['unsubscribed']++;
                }
            } else {
                // Simulate bounce
                if ($this->shouldHappen($bounceRate)) {
                    $this->simulateEvent($messageId, $subscriber->email, 'bounced');
                    $totalStats['bounced']++;
                }
            }

            // Simulate complaints
            if ($this->shouldHappen($complainRate)) {
                $this->simulateEvent($messageId, $subscriber->email, 'complained');
                $totalStats['complained']++;
            }

            $this->info("📨 Sent to: {$subscriber->email} ({$subscriber->getFullName()})");
            $this->info("📋 Subject: {$emailSubject}");

            // Display running totals every 10 iterations
            if ($iteration % 10 === 0) {
                $this->displayLoopStats($totalStats, $iteration);
            }

            // Calculate sleep time
            $processingTime = microtime(true) - $startTime;
            $sleepTime = max(0, $loopInterval - $processingTime);

            if ($sleepTime > 0) {
                $this->info("⏱️  Waiting {$sleepTime}s until next iteration...");
                sleep((int) $sleepTime);
            }

            // Process signals if available
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            $this->newLine();
        }

        if ($shouldStop) {
            $this->newLine();
            $this->info('🛑 Loop mode stopped by user');
        }

        return Command::SUCCESS;
    }

    /**
     * Run a single campaign.
     */
    private function runSingleCampaign(DistributionList $list, array $users, string $campaignId, string $templateName, string $subject, int $batchSize, int $delay, int $emailsPerUser, int $deliveryRate, int $openRate, int $clickRate, int $bounceRate, int $complainRate, int $unsubscribeRate): int
    {
        $totalEmails = count($users) * $emailsPerUser;
        $progressBar = $this->output->createProgressBar($totalEmails);
        $progressBar->start();

        $stats = [
            'sent' => 0,
            'delivered' => 0,
            'opened' => 0,
            'clicked' => 0,
            'bounced' => 0,
            'complained' => 0,
            'unsubscribed' => 0,
        ];

        $emailCount = 0;
        $batchCount = 0;

        // Get all active subscribers from the distribution list
        $subscribers = $list->activeSubscribers()->get();

        // Process emails in batches
        for ($userIndex = 0; $userIndex < count($users); $userIndex++) {
            $user = $users[$userIndex];

            for ($emailIndex = 0; $emailIndex < $emailsPerUser; $emailIndex++) {
                $emailCount++;
                $batchCount++;

                $messageId = $this->generateMessageId();
                $emailSubject = $this->generateEmailSubject($subject, $emailIndex + 1, $emailsPerUser);

                // Prepare template variables based on template type
                $templateVariables = $this->prepareTemplateVariables($templateName, $user, $campaignId, $emailSubject, $emailIndex + 1);

                // Dispatch email job
                $this->emailDispatcher->sendToOne(
                    $user['email'],
                    $templateName,
                    $templateVariables,
                    [
                        'from' => [
                            'address' => 'noreply@example.com',
                            'name' => 'Test App',
                        ],
                        'subject' => $emailSubject,
                    ]
                );

                // Create outbound email record for tracking
                $this->trackingService->createOutboundEmail($messageId, $user['email'], [
                    'campaign_id' => $campaignId,
                    'user_id' => $user['id'],
                    'template_name' => $templateName,
                    'subject' => $emailSubject,
                    'from_address' => 'noreply@example.com',
                    'from_name' => 'Test App',
                    'metadata' => [
                        'mock_campaign' => true,
                        'user_index' => $userIndex,
                        'email_index' => $emailIndex,
                        'user_data' => $user,
                        'distribution_list_id' => $list->id,
                    ],
                ]);

                $stats['sent']++;

                // Simulate delivery
                if ($this->shouldHappen($deliveryRate)) {
                    $this->simulateEvent($messageId, $user['email'], 'delivered');
                    $stats['delivered']++;

                    // Simulate opens
                    if ($this->shouldHappen($openRate)) {
                        $this->simulateEvent($messageId, $user['email'], 'opened');
                        $stats['opened']++;

                        // Simulate clicks
                        if ($this->shouldHappen($clickRate)) {
                            $this->simulateEvent($messageId, $user['email'], 'clicked');
                            $stats['clicked']++;
                        }
                    }

                    // Simulate unsubscribes (can happen after opening)
                    if ($this->shouldHappen($unsubscribeRate)) {
                        $this->simulateEvent($messageId, $user['email'], 'unsubscribed');
                        $stats['unsubscribed']++;
                    }
                } else {
                    // Simulate bounce
                    if ($this->shouldHappen($bounceRate)) {
                        $this->simulateEvent($messageId, $user['email'], 'bounced');
                        $stats['bounced']++;
                    }
                }

                // Simulate complaints (can happen regardless of delivery)
                if ($this->shouldHappen($complainRate)) {
                    $this->simulateEvent($messageId, $user['email'], 'complained');
                    $stats['complained']++;
                }

                $progressBar->advance();

                // Process batch delay
                if ($batchCount >= $batchSize) {
                    if ($delay > 0) {
                        usleep($delay * 1000); // Convert to microseconds
                    }
                    $batchCount = 0;
                }

                // Add some realistic timing variation
                usleep(rand(1000, 5000)); // 1-5ms delay
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Display final statistics
        $this->displayFinalStats($stats, $totalEmails);

        // Show cumulative stats from the tracking service
        $this->displayTrackingStats($campaignId);

        // Show distribution list statistics
        $this->displayDistributionListStats($list);

        // Show user statistics
        $this->displayUserStats($users, $emailsPerUser);

        $this->info('✅ Mock campaign simulation completed!');

        return Command::SUCCESS;
    }

    /**
     * Select real users from the users table.
     */
    private function getRealUsers(int $numUsers): array
    {
        $userModel = config('mailgun.user_model', \App\Models\User::class);

        return $userModel::query()
            ->orderBy('id')
            ->limit($numUsers)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->name,
                    'domain' => substr(strrchr($user->email, '@'), 1),
                ];
            })
            ->toArray();
    }

    /**
     * Generate email subject with variation.
     */
    private function generateEmailSubject(string $baseSubject, int $emailIndex, int $totalEmails): string
    {
        if ($totalEmails === 1) {
            return $baseSubject;
        }

        $suffixes = [
            "Part {$emailIndex} of {$totalEmails}",
            "Email {$emailIndex}",
            "Message {$emailIndex}",
            "Update {$emailIndex}",
        ];

        $suffix = $suffixes[array_rand($suffixes)];

        return "{$baseSubject} - {$suffix}";
    }

    /**
     * Display user statistics.
     */
    private function displayUserStats(array $users, int $emailsPerUser): void
    {
        $this->info('👥 User Statistics:');

        $domainStats = [];
        foreach ($users as $user) {
            $domain = $user['domain'];
            $domainStats[$domain] = ($domainStats[$domain] ?? 0) + 1;
        }

        $domainRows = [];
        foreach ($domainStats as $domain => $count) {
            $domainRows[] = [$domain, $count, round(($count / count($users)) * 100, 1).'%'];
        }

        $this->table(
            ['Domain', 'Users', 'Percentage'],
            $domainRows
        );

        $this->info('📧 Total emails sent: '.(count($users) * $emailsPerUser));
        $this->info('👤 Unique users: '.count($users));
        $this->info("📨 Emails per user: {$emailsPerUser}");
    }

    /**
     * Validate that a template exists and is active.
     */
    private function validateTemplate(string $templateSlug): bool
    {
        $template = \Inbounder\Models\EmailTemplate::where('slug', $templateSlug)
            ->where('is_active', true)
            ->first();

        return $template !== null;
    }

    /**
     * Prepare template variables based on template type.
     */
    private function prepareTemplateVariables(string $templateName, array $user, string $campaignId, string $subject, int $index): array
    {
        $baseVariables = [
            'name' => $user['full_name'],
            'campaign_id' => $campaignId,
            'user_id' => $user['id'],
        ];

        switch ($templateName) {
            case 'newsletter':
                return array_merge($baseVariables, [
                    'subject' => $subject,
                    'content' => $this->generateNewsletterContent($index),
                    'unsubscribe_url' => "https://example.com/unsubscribe?email={$user['email']}&campaign={$campaignId}",
                ]);

            case 'welcome':
                return array_merge($baseVariables, [
                    'app_name' => 'Test Application',
                    'login_url' => 'https://example.com/login',
                ]);

            case 'notification':
                return array_merge($baseVariables, [
                    'subject' => $subject,
                    'message' => $this->generateNotificationMessage($index),
                    'action_url' => "https://example.com/action?user={$user['id']}&campaign={$campaignId}",
                ]);

            case 'campaign':
                return array_merge($baseVariables, [
                    'campaign_subject' => $subject,
                    'campaign_content' => $this->generateCampaignContent($index),
                    'cta_text' => 'Learn More',
                    'cta_url' => "https://example.com/campaign?user={$user['id']}&campaign={$campaignId}",
                ]);

            default:
                // For custom templates, provide basic variables
                return array_merge($baseVariables, [
                    'subject' => $subject,
                    'content' => $this->generateGenericContent($index),
                ]);
        }
    }

    /**
     * Generate newsletter content.
     */
    private function generateNewsletterContent(int $index): string
    {
        $contents = [
            'Stay updated with our latest news and updates. We have exciting developments coming your way!',
            'This month we\'re featuring new products and services that we think you\'ll love.',
            'Don\'t miss out on our exclusive offers and special promotions available only to our subscribers.',
            'We\'ve been working hard to bring you the best experience possible. Here\'s what\'s new.',
            'Thank you for being part of our community. Here\'s what we\'ve been up to lately.',
        ];

        return $contents[$index % count($contents)];
    }

    /**
     * Generate notification message.
     */
    private function generateNotificationMessage(int $index): string
    {
        $messages = [
            'Your account has been successfully updated with the latest features.',
            'We\'ve detected unusual activity on your account. Please review and confirm.',
            'Your subscription has been renewed successfully. Thank you for your continued support.',
            'A new message is waiting for you in your inbox.',
            'Your order has been processed and is on its way to you.',
        ];

        return $messages[$index % count($messages)];
    }

    /**
     * Generate campaign content.
     */
    private function generateCampaignContent(int $index): string
    {
        $contents = [
            'Discover our amazing new features that will revolutionize your experience.',
            'Limited time offer! Don\'t miss out on our exclusive deals and discounts.',
            'Join thousands of satisfied customers who have already transformed their workflow.',
            'We\'re excited to announce our biggest update yet with incredible new capabilities.',
            'Take your productivity to the next level with our cutting-edge solutions.',
        ];

        return $contents[$index % count($contents)];
    }

    /**
     * Generate generic content.
     */
    private function generateGenericContent(int $index): string
    {
        return "This is a test email #{$index} with generic content for template testing.";
    }

    /**
     * List available templates.
     */
    private function listAvailableTemplates(): void
    {
        $templates = \Inbounder\Models\EmailTemplate::where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'slug', 'category']);

        if ($templates->isEmpty()) {
            $this->line('  No active templates found.');

            return;
        }

        $rows = [];
        foreach ($templates as $template) {
            $rows[] = [
                $template->name,
                $template->slug,
                $template->category ?: 'None',
            ];
        }

        $this->table(['Name', 'Slug', 'Category'], $rows);
    }

    /**
     * Display loop mode statistics.
     */
    private function displayLoopStats(array $stats, int $iteration): void
    {
        $this->info('📊 Loop Statistics (Iteration '.$iteration.'):');
        $this->table(
            ['Metric', 'Count', 'Rate'],
            [
                ['Sent', $stats['sent'], '100%'],
                ['Delivered', $stats['delivered'], round(($stats['delivered'] / $stats['sent']) * 100, 1).'%'],
                ['Opened', $stats['opened'], round(($stats['opened'] / $stats['sent']) * 100, 1).'%'],
                ['Clicked', $stats['clicked'], round(($stats['clicked'] / $stats['sent']) * 100, 1).'%'],
                ['Bounced', $stats['bounced'], round(($stats['bounced'] / $stats['sent']) * 100, 1).'%'],
                ['Complained', $stats['complained'], round(($stats['complained'] / $stats['sent']) * 100, 1).'%'],
                ['Unsubscribed', $stats['unsubscribed'], round(($stats['unsubscribed'] / $stats['sent']) * 100, 1).'%'],
            ]
        );
        $this->newLine();
    }

    /**
     * Generate a realistic Mailgun message ID.
     */
    private function generateMessageId(): string
    {
        $timestamp = time();
        $random = Str::random(8);

        return "msg_{$timestamp}_{$random}@example.com";
    }

    /**
     * Determine if an event should happen based on percentage.
     */
    private function shouldHappen(int $percentage): bool
    {
        return rand(1, 100) <= $percentage;
    }

    /**
     * Simulate a webhook event.
     */
    private function simulateEvent(string $messageId, string $recipient, string $eventType): void
    {
        $timestamp = time() + rand(-300, 300); // ±5 minutes variation

        $eventData = [
            'event_type' => $eventType,
            'message_id' => $messageId,
            'recipient' => $recipient,
            'domain' => 'example.com',
            'ip' => $this->getRandomIp(),
            'country' => $this->getRandomCountry(),
            'region' => $this->getRandomRegion(),
            'city' => $this->getRandomCity(),
            'user_agent' => $this->getRandomUserAgent(),
            'device_type' => $this->getRandomDeviceType(),
            'client_type' => $this->getRandomClientType(),
            'client_name' => $this->getRandomClientName(),
            'client_os' => $this->getRandomClientOs(),
            'event_timestamp' => date('Y-m-d H:i:s', $timestamp),
            'raw_data' => [
                'event' => $eventType,
                'timestamp' => $timestamp,
                'message-id' => $messageId,
                'recipient' => $recipient,
                'domain' => 'example.com',
                'ip' => $this->getRandomIp(),
                'country' => $this->getRandomCountry(),
                'region' => $this->getRandomRegion(),
                'city' => $this->getRandomCity(),
                'user-agent' => $this->getRandomUserAgent(),
                'device-type' => $this->getRandomDeviceType(),
                'client-type' => $this->getRandomClientType(),
                'client-name' => $this->getRandomClientName(),
                'client-os' => $this->getRandomClientOs(),
            ],
        ];

        // Create event log entry
        MailgunEvent::create($eventData);

        // Update outbound email tracking
        $this->trackingService->updateFromWebhook($messageId, $eventType, [
            'timestamp' => $timestamp,
        ]);
    }

    /**
     * Display final statistics.
     */
    private function displayFinalStats(array $stats, int $total): void
    {
        $this->info('📊 Campaign Statistics:');
        $this->table(
            ['Metric', 'Count', 'Percentage'],
            [
                ['Sent', $stats['sent'], '100%'],
                ['Delivered', $stats['delivered'], round(($stats['delivered'] / $total) * 100, 1).'%'],
                ['Opened', $stats['opened'], round(($stats['opened'] / $total) * 100, 1).'%'],
                ['Clicked', $stats['clicked'], round(($stats['clicked'] / $total) * 100, 1).'%'],
                ['Bounced', $stats['bounced'], round(($stats['bounced'] / $total) * 100, 1).'%'],
                ['Complained', $stats['complained'], round(($stats['complained'] / $total) * 100, 1).'%'],
                ['Unsubscribed', $stats['unsubscribed'], round(($stats['unsubscribed'] / $total) * 100, 1).'%'],
            ]
        );
    }

    /**
     * Display tracking service statistics.
     */
    private function displayTrackingStats(string $campaignId): void
    {
        $this->info('🔍 Tracking Service Statistics:');

        $cumulativeStats = $this->trackingService->getCumulativeCampaignStats($campaignId);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Sent', $cumulativeStats['total_sent']],
                ['Delivered', $cumulativeStats['delivered']],
                ['Opened', $cumulativeStats['opened']],
                ['Clicked', $cumulativeStats['clicked']],
                ['Bounced', $cumulativeStats['bounced']],
                ['Complained', $cumulativeStats['complained']],
                ['Unsubscribed', $cumulativeStats['unsubscribed']],
            ]
        );
    }

    /**
     * Get a random IP address.
     */
    private function getRandomIp(): string
    {
        $ips = [
            '192.168.1.1', '10.0.0.1', '172.16.0.1', '203.0.113.1',
            '198.51.100.1', '203.0.113.2', '198.51.100.2', '203.0.113.3',
        ];

        return $ips[array_rand($ips)];
    }

    /**
     * Get a random country.
     */
    private function getRandomCountry(): string
    {
        $countries = ['US', 'CA', 'GB', 'DE', 'FR', 'AU', 'JP', 'BR'];

        return $countries[array_rand($countries)];
    }

    /**
     * Get a random region.
     */
    private function getRandomRegion(): string
    {
        $regions = ['CA', 'NY', 'TX', 'FL', 'WA', 'IL', 'PA', 'OH'];

        return $regions[array_rand($regions)];
    }

    /**
     * Get a random city.
     */
    private function getRandomCity(): string
    {
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego'];

        return $cities[array_rand($cities)];
    }

    /**
     * Get a random user agent.
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15',
            'Mozilla/5.0 (Android 11; Mobile; rv:68.0) Gecko/68.0 Firefox/68.0',
        ];

        return $userAgents[array_rand($userAgents)];
    }

    /**
     * Get a random device type.
     */
    private function getRandomDeviceType(): string
    {
        $deviceTypes = ['desktop', 'mobile', 'tablet'];

        return $deviceTypes[array_rand($deviceTypes)];
    }

    /**
     * Get a random client type.
     */
    private function getRandomClientType(): string
    {
        $clientTypes = ['browser', 'email_client', 'mobile_app'];

        return $clientTypes[array_rand($clientTypes)];
    }

    /**
     * Get a random client name.
     */
    private function getRandomClientName(): string
    {
        $clientNames = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Outlook', 'Gmail', 'Apple Mail'];

        return $clientNames[array_rand($clientNames)];
    }

    /**
     * Get a random client OS.
     */
    private function getRandomClientOs(): string
    {
        $clientOs = ['Windows', 'macOS', 'Linux', 'iOS', 'Android'];

        return $clientOs[array_rand($clientOs)];
    }

    /**
     * Get a random active template slug.
     */
    private function getRandomActiveTemplateSlug(): ?string
    {
        $template = \Inbounder\Models\EmailTemplate::where('is_active', true)
            ->inRandomOrder()
            ->first();

        return $template ? $template->slug : null;
    }

    /**
     * Display distribution list statistics.
     */
    private function displayDistributionListStats(DistributionList $list): void
    {
        $this->info('📧 Distribution List Statistics:');

        $stats = $list->getStats();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Subscribers', $stats['total_subscribers']],
                ['Active Subscribers', $stats['active_subscribers']],
                ['Inactive Subscribers', $stats['inactive_subscribers']],
                ['Subscription Rate', $stats['subscription_rate'].'%'],
            ]
        );

        $this->info("📧 List: {$list->name} ({$list->slug})");
        $this->info('📝 Category: '.($list->category ?: 'None'));
        $this->info("📅 Created: {$list->created_at->format('Y-m-d H:i:s')}");
    }
}
