<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Inbounder\Jobs\SendTemplatedEmailJob;
use Inbounder\Services\TemplatedEmailJobDispatcher;
use Inbounder\Services\QueueService;
use PHPUnit\Framework\Attributes\Test;
use Inbounder\Tests\TestCase;

class TemplatedEmailJobDispatcherTest extends TestCase
{
    private TemplatedEmailJobDispatcher $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new TemplatedEmailJobDispatcher(new QueueService());
    }

    #[Test]
    public function it_dispatches_job_to_single_recipient()
    {
        Bus::fake();

        $recipient = 'test@example.com';
        $templateSlug = 'welcome-email';
        $variables = ['name' => 'John Doe'];
        $options = ['queue' => 'emails'];

        $this->dispatcher->sendToOne($recipient, $templateSlug, $variables, $options);

        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) use ($recipient, $templateSlug, $variables, $options) {
            return $job->recipient === $recipient
                && $job->templateSlug === $templateSlug
                && $job->variables === $variables
                && $job->options === $options;
        });
    }

    #[Test]
    public function it_dispatches_job_to_single_recipient_with_default_options()
    {
        Bus::fake();

        $recipient = 'test@example.com';
        $templateSlug = 'welcome-email';
        $variables = ['name' => 'John Doe'];

        $this->dispatcher->sendToOne($recipient, $templateSlug, $variables);

        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) use ($recipient, $templateSlug, $variables) {
            return $job->recipient === $recipient
                && $job->templateSlug === $templateSlug
                && $job->variables === $variables
                && $job->options === [];
        });
    }

    #[Test]
    public function it_dispatches_jobs_to_multiple_recipients()
    {
        Bus::fake();

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User One', 'company' => 'Company A'],
            ['email' => 'user2@example.com', 'name' => 'User Two', 'company' => 'Company B'],
        ];
        $templateSlug = 'newsletter';
        $options = ['queue' => 'newsletters'];

        $this->dispatcher->sendToMany($recipients, $templateSlug, $options);

        Bus::assertDispatched(SendTemplatedEmailJob::class, 2);

        // Check first job
        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user1@example.com'
                && $job->templateSlug === 'newsletter'
                && $job->variables === ['name' => 'User One', 'company' => 'Company A']
                && $job->options === ['queue' => 'newsletters'];
        });

        // Check second job
        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user2@example.com'
                && $job->templateSlug === 'newsletter'
                && $job->variables === ['name' => 'User Two', 'company' => 'Company B']
                && $job->options === ['queue' => 'newsletters'];
        });
    }

    #[Test]
    public function it_dispatches_jobs_to_multiple_recipients_with_default_options()
    {
        Bus::fake();

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User One'],
            ['email' => 'user2@example.com', 'name' => 'User Two'],
        ];
        $templateSlug = 'newsletter';

        $this->dispatcher->sendToMany($recipients, $templateSlug);

        Bus::assertDispatched(SendTemplatedEmailJob::class, 2);

        // Check first job
        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user1@example.com'
                && $job->templateSlug === 'newsletter'
                && $job->variables === ['name' => 'User One']
                && $job->options === [];
        });

        // Check second job
        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user2@example.com'
                && $job->templateSlug === 'newsletter'
                && $job->variables === ['name' => 'User Two']
                && $job->options === [];
        });
    }

    #[Test]
    public function it_handles_recipients_with_only_email()
    {
        Bus::fake();

        $recipients = [
            ['email' => 'user1@example.com'],
            ['email' => 'user2@example.com'],
        ];
        $templateSlug = 'simple-notification';

        $this->dispatcher->sendToMany($recipients, $templateSlug);

        Bus::assertDispatched(SendTemplatedEmailJob::class, 2);

        // Check first job
        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user1@example.com'
                && $job->templateSlug === 'simple-notification'
                && $job->variables === []
                && $job->options === [];
        });

        // Check second job
        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user2@example.com'
                && $job->templateSlug === 'simple-notification'
                && $job->variables === []
                && $job->options === [];
        });
    }

    #[Test]
    public function it_creates_batch_with_multiple_recipients()
    {
        Bus::fake();

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User One', 'company' => 'Company A'],
            ['email' => 'user2@example.com', 'name' => 'User Two', 'company' => 'Company B'],
            ['email' => 'user3@example.com', 'name' => 'User Three', 'company' => 'Company C'],
        ];
        $templateSlug = 'campaign-email';
        $options = ['queue' => 'campaigns'];

        $batch = $this->dispatcher->sendBatch($recipients, $templateSlug, $options);

        Bus::assertBatched(function ($batch) {
            return $batch->name === 'Templated Email Campaign'
                && count($batch->jobs) === 3;
        });

        // Verify the batch was dispatched
        $this->assertInstanceOf(Batch::class, $batch);
    }

    #[Test]
    public function it_creates_batch_with_default_options()
    {
        Bus::fake();

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User One'],
            ['email' => 'user2@example.com', 'name' => 'User Two'],
        ];
        $templateSlug = 'campaign-email';

        $batch = $this->dispatcher->sendBatch($recipients, $templateSlug);

        Bus::assertBatched(function ($batch) {
            return $batch->name === 'Templated Email Campaign'
                && count($batch->jobs) === 2;
        });

        // Verify the batch was dispatched
        $this->assertInstanceOf(Batch::class, $batch);
    }

    #[Test]
    public function it_creates_batch_with_single_recipient()
    {
        Bus::fake();

        $recipients = [
            ['email' => 'user1@example.com', 'name' => 'User One'],
        ];
        $templateSlug = 'single-campaign';

        $batch = $this->dispatcher->sendBatch($recipients, $templateSlug);

        Bus::assertBatched(function ($batch) {
            return $batch->name === 'Templated Email Campaign'
                && count($batch->jobs) === 1;
        });

        // Verify the batch was dispatched
        $this->assertInstanceOf(Batch::class, $batch);
    }

    #[Test]
    public function it_creates_batch_with_empty_recipients()
    {
        Bus::fake();

        $recipients = [];
        $templateSlug = 'empty-campaign';

        $batch = $this->dispatcher->sendBatch($recipients, $templateSlug);

        Bus::assertBatched(function ($batch) {
            return $batch->name === 'Templated Email Campaign'
                && count($batch->jobs) === 0;
        });

        // Verify the batch was dispatched
        $this->assertInstanceOf(Batch::class, $batch);
    }

    #[Test]
    public function it_handles_recipients_with_complex_variables()
    {
        Bus::fake();

        $recipients = [
            [
                'email' => 'user1@example.com',
                'name' => 'User One',
                'company' => 'Company A',
                'role' => 'Manager',
                'department' => 'Engineering',
                'custom_field' => 'Custom Value',
            ],
        ];
        $templateSlug = 'complex-template';
        $options = ['priority' => 'high'];

        $this->dispatcher->sendToMany($recipients, $templateSlug, $options);

        Bus::assertDispatched(SendTemplatedEmailJob::class, function ($job) {
            return $job->recipient === 'user1@example.com'
                && $job->templateSlug === 'complex-template'
                && $job->variables === [
                    'name' => 'User One',
                    'company' => 'Company A',
                    'role' => 'Manager',
                    'department' => 'Engineering',
                    'custom_field' => 'Custom Value',
                ]
                && $job->options === ['priority' => 'high'];
        });
    }
}
