<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Inbounder\Jobs\SendTemplatedEmailJob;

/**
 * Service to dispatch templated email jobs (single, many, or batch).
 */
class TemplatedEmailJobDispatcher
{
    public function __construct(
        private readonly QueueService $queueService
    ) {}

    /**
     * Dispatch a job to send a single templated email.
     */
    public function sendToOne(string $recipient, string $templateSlug, array $variables, array $options = []): void
    {
        $job = new SendTemplatedEmailJob($recipient, $templateSlug, $variables, $options);

        // Apply queue configuration
        $this->configureJob($job);

        dispatch($job);
    }

    /**
     * Dispatch jobs to send templated emails to many recipients.
     *
     * @param  array  $recipients  Array of ['email' => ..., ...variables]
     */
    public function sendToMany(array $recipients, string $templateSlug, array $options = []): void
    {
        foreach ($recipients as $recipient) {
            $email = $recipient['email'];
            $variables = $recipient;
            unset($variables['email']);

            $job = new SendTemplatedEmailJob($email, $templateSlug, $variables, $options);
            $this->configureJob($job);

            dispatch($job);
        }
    }

    /**
     * Dispatch a batch of jobs for a campaign (returns the Batch object).
     *
     * @param  array  $recipients  Array of ['email' => ..., ...variables]
     */
    public function sendBatch(array $recipients, string $templateSlug, array $options = []): Batch
    {
        $jobs = [];
        foreach ($recipients as $recipient) {
            $email = $recipient['email'];
            $variables = $recipient;
            unset($variables['email']);

            $job = new SendTemplatedEmailJob($email, $templateSlug, $variables, $options);
            $this->configureJob($job);

            $jobs[] = $job;
        }

        $batch = Bus::batch($jobs)->name('Templated Email Campaign');

        // Apply batch configuration if enabled
        if ($this->queueService->isBatchEnabled()) {
            $batchConfig = $this->queueService->getBatchConfig();
            $batch->allowFailures()
                  ->onQueue($this->queueService->getTemplatedEmailsQueue());
        }

        return $batch->dispatch();
    }

    /**
     * Configure a job with queue settings.
     */
    private function configureJob(SendTemplatedEmailJob $job): void
    {
        if (!$this->queueService->isEnabled()) {
            return;
        }

        $jobConfig = $this->queueService->getJobConfig('templated_emails');

        // Set queue name
        $job->onQueue($jobConfig['queue']);

        // Set connection if different from default
        if ($jobConfig['connection'] !== 'default') {
            $job->onConnection($jobConfig['connection']);
        }
    }
}
