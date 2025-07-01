<?php

declare(strict_types=1);

namespace Inbounder\Providers;

use Illuminate\Support\ServiceProvider;
use Inbounder\Http\Controllers\MailgunController;
use Inbounder\Http\Middleware\VerifyMailgunWebhook;
use Inbounder\Services\DistributionListService;
use Inbounder\Services\EmailTemplateService;
use Inbounder\Services\MailgunEmailSender;
use Inbounder\Services\MailgunService;
use Inbounder\Services\MailgunTrackingService;
use Inbounder\Services\TemplatedEmailJobDispatcher;
use Inbounder\Services\QueueService;

/**
 * Service provider for Mailgun integration.
 *
 * This provider registers routes, middleware, and configuration for handling
 * Mailgun inbound emails and webhooks.
 */
class MailgunServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailgun.php', 'mailgun');

        $this->app->singleton(MailgunService::class, function ($app) {
            return new MailgunService($app->make(MailgunTrackingService::class));
        });

        $this->app->singleton(MailgunTrackingService::class, function ($app) {
            return new MailgunTrackingService;
        });

        $this->app->singleton(MailgunEmailSender::class, function ($app) {
            return new MailgunEmailSender($app->make(MailgunTrackingService::class));
        });

        $this->app->singleton(EmailTemplateService::class, function ($app) {
            return new EmailTemplateService;
        });

        $this->app->singleton(TemplatedEmailJobDispatcher::class, function ($app) {
            return new TemplatedEmailJobDispatcher($app->make(QueueService::class));
        });

        $this->app->singleton(DistributionListService::class, function ($app) {
            return new DistributionListService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMiddleware();
        $this->registerConfig();
        $this->registerMailer();
        // $this->loadMigrations(); // Commented out to prevent auto-loading migrations
        $this->registerCommands();
    }

    /**
     * Register the Mailgun routes.
     */
    private function registerRoutes(): void
    {
        $router = $this->app['router'];

        $router->group([
            'prefix' => 'api/mail',
            'middleware' => ['api'],
        ], function ($router) {
            $router->post('inbound', [MailgunController::class, 'inbound'])
                ->middleware('verify.mailgun.webhook')
                ->name('mailgun.inbound');
            $router->post('webhooks', [MailgunController::class, 'webhook'])
                ->middleware('verify.mailgun.webhook')
                ->name('mailgun.webhook');
        });
    }

    /**
     * Register the Mailgun middleware.
     */
    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('verify.mailgun.webhook', VerifyMailgunWebhook::class);
    }

    /**
     * Register the Mailgun configuration.
     */
    private function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/mailgun.php' => config_path('mailgun.php'),
        ], 'mailgun-config');
    }

    /**
     * Register the Mailgun mailer configuration.
     */
    private function registerMailer(): void
    {
        $config = $this->app['config'];

        // Only configure if outbound is enabled and credentials are available
        if (! config('mailgun.outbound.enabled', true) ||
            ! config('mailgun.domain') ||
            ! config('mailgun.secret')) {
            return;
        }

        // Get the current mailers configuration
        $mailers = $config->get('mail.mailers', []);

        // Add Mailgun mailer configuration
        $mailers['mailgun'] = [
            'transport' => 'mailgun',
            'domain' => config('mailgun.domain'),
            'secret' => config('mailgun.secret'),
            'endpoint' => config('mailgun.endpoint', 'api.mailgun.net'),
        ];

        // Update the mail configuration
        $config->set('mail.mailers', $mailers);

        // Set Mailgun as default mailer
        $config->set('mail.default', 'mailgun');

        // Set default from address from Mailgun config
        $config->set('mail.from', config('mailgun.outbound.default_from'));
    }

    /**
     * Load the Mailgun migrations.
     */
    private function loadMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the Mailgun console commands.
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Inbounder\Console\Commands\Demo\MockEmailCampaign::class,
                \Inbounder\Console\Commands\Demo\SetupDefaultTemplates::class,
                \Inbounder\Console\Commands\EmailTemplates\CreateEmailTemplate::class,
                \Inbounder\Console\Commands\EmailTemplates\ListEmailTemplates::class,
                \Inbounder\Console\Commands\EmailTemplates\SendTemplatedEmail::class,
                \Inbounder\Console\Commands\DistributionLists\CreateDistributionList::class,
                \Inbounder\Console\Commands\DistributionLists\ListDistributionLists::class,
                \Inbounder\Console\Commands\DistributionLists\AddSubscribers::class,
                \Inbounder\Console\Commands\DistributionLists\RemoveSubscribers::class,
                \Inbounder\Console\Commands\DistributionLists\SendCampaign::class,
                \Inbounder\Console\Commands\DistributionLists\ManageSubscribers::class,
                \Inbounder\Console\Commands\ProcessInboundEmailsCommand::class,
            ]);
        }
    }
}
