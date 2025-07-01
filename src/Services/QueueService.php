<?php

declare(strict_types=1);

namespace Inbounder\Services;

use Illuminate\Support\Facades\Config;

/**
 * Service for managing queue configuration and operations.
 */
class QueueService
{
    /**
     * Get the queue configuration.
     */
    public function getConfig(): array
    {
        return Config::get('inbounder.mailgun.queue', []);
    }

    /**
     * Check if custom queue settings are enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->getConfig()['enabled'] ?? false);
    }

    /**
     * Get the default queue name.
     */
    public function getDefaultQueue(): string
    {
        return $this->getConfig()['default_queue'] ?? 'mailgun';
    }

    /**
     * Get the queue name for a specific job type.
     */
    public function getQueueName(string $jobType): string
    {
        if (!$this->isEnabled()) {
            return $this->getDefaultQueue();
        }

        $queues = $this->getConfig()['queues'] ?? [];

        return $queues[$jobType] ?? $this->getDefaultQueue();
    }

    /**
     * Get the queue name for templated emails.
     */
    public function getTemplatedEmailsQueue(): string
    {
        return $this->getQueueName('templated_emails');
    }

    /**
     * Get the queue name for webhook events.
     */
    public function getWebhookEventsQueue(): string
    {
        return $this->getQueueName('webhook_events');
    }

    /**
     * Get the queue name for inbound emails.
     */
    public function getInboundEmailsQueue(): string
    {
        return $this->getQueueName('inbound_emails');
    }

    /**
     * Get the queue name for tracking jobs.
     */
    public function getTrackingQueue(): string
    {
        return $this->getQueueName('tracking');
    }

    /**
     * Get the queue connection driver.
     */
    public function getConnectionDriver(): string
    {
        $connection = $this->getConfig()['connection'] ?? [];

        return $connection['driver'] ?? 'default';
    }

    /**
     * Get retry configuration.
     */
    public function getRetryConfig(): array
    {
        $connection = $this->getConfig()['connection'] ?? [];
        $retry = $connection['retry'] ?? [];

        return [
            'max_attempts' => $retry['max_attempts'] ?? 3,
            'delay' => $retry['delay'] ?? 60,
            'backoff' => $retry['backoff'] ?? true,
        ];
    }

    /**
     * Get timeout configuration.
     */
    public function getTimeoutConfig(): array
    {
        $connection = $this->getConfig()['connection'] ?? [];
        $timeout = $connection['timeout'] ?? [];

        return [
            'job_timeout' => $timeout['job_timeout'] ?? 300,
            'queue_timeout' => $timeout['queue_timeout'] ?? 600,
        ];
    }

    /**
     * Get batch processing configuration.
     */
    public function getBatchConfig(): array
    {
        $batch = $this->getConfig()['batch'] ?? [];

        return [
            'enabled' => $batch['enabled'] ?? true,
            'max_size' => $batch['max_size'] ?? 100,
            'delay' => $batch['delay'] ?? 5,
        ];
    }

    /**
     * Check if batch processing is enabled.
     */
    public function isBatchEnabled(): bool
    {
        return (bool) ($this->getBatchConfig()['enabled'] ?? true);
    }

    /**
     * Get the maximum batch size.
     */
    public function getMaxBatchSize(): int
    {
        return (int) ($this->getBatchConfig()['max_size'] ?? 100);
    }

    /**
     * Get the batch delay in seconds.
     */
    public function getBatchDelay(): int
    {
        return (int) ($this->getBatchConfig()['delay'] ?? 5);
    }

    /**
     * Get all queue names as an array.
     */
    public function getAllQueueNames(): array
    {
        if (!$this->isEnabled()) {
            return [$this->getDefaultQueue()];
        }

        $queues = $this->getConfig()['queues'] ?? [];
        $queueNames = array_values($queues);

        // Ensure default queue is included
        $defaultQueue = $this->getDefaultQueue();
        if (!in_array($defaultQueue, $queueNames)) {
            $queueNames[] = $defaultQueue;
        }

        return array_unique($queueNames);
    }

    /**
     * Get queue configuration for a specific job type.
     */
    public function getJobConfig(string $jobType): array
    {
        return [
            'queue' => $this->getQueueName($jobType),
            'connection' => $this->getConnectionDriver(),
            'retry' => $this->getRetryConfig(),
            'timeout' => $this->getTimeoutConfig(),
        ];
    }
}
