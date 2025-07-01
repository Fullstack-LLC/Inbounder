<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Inbounder\Services\QueueService;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class QueueServiceTest extends TestCase
{
    private QueueService $queueService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queueService = new QueueService();
    }

    #[Test]
    public function it_returns_default_config_when_queue_not_enabled(): void
    {
        Config::set('inbounder.mailgun.queue.enabled', false);

        $this->assertFalse($this->queueService->isEnabled());
        $this->assertSame('mailgun', $this->queueService->getDefaultQueue());
        $this->assertSame('mailgun', $this->queueService->getTemplatedEmailsQueue());
    }

    #[Test]
    public function it_returns_custom_queue_names_when_enabled(): void
    {
        Config::set('inbounder.mailgun.queue', [
            'enabled' => true,
            'default_queue' => 'custom-mailgun',
            'queues' => [
                'templated_emails' => 'email-queue',
                'webhook_events' => 'webhook-queue',
                'inbound_emails' => 'inbound-queue',
                'tracking' => 'tracking-queue',
            ],
        ]);

        $this->assertTrue($this->queueService->isEnabled());
        $this->assertSame('custom-mailgun', $this->queueService->getDefaultQueue());
        $this->assertSame('email-queue', $this->queueService->getTemplatedEmailsQueue());
        $this->assertSame('webhook-queue', $this->queueService->getWebhookEventsQueue());
        $this->assertSame('inbound-queue', $this->queueService->getInboundEmailsQueue());
        $this->assertSame('tracking-queue', $this->queueService->getTrackingQueue());
    }

    #[Test]
    public function it_falls_back_to_default_queue_for_unknown_job_types(): void
    {
        Config::set('inbounder.mailgun.queue', [
            'enabled' => true,
            'default_queue' => 'default-mailgun',
            'queues' => [
                'templated_emails' => 'email-queue',
            ],
        ]);

        $this->assertSame('default-mailgun', $this->queueService->getQueueName('unknown_job_type'));
    }

    #[Test]
    public function it_returns_connection_configuration(): void
    {
        Config::set('inbounder.mailgun.queue.connection', [
            'driver' => 'redis',
            'retry' => [
                'max_attempts' => 5,
                'delay' => 120,
                'backoff' => false,
            ],
            'timeout' => [
                'job_timeout' => 600,
                'queue_timeout' => 1200,
            ],
        ]);

        $this->assertSame('redis', $this->queueService->getConnectionDriver());

        $retryConfig = $this->queueService->getRetryConfig();
        $this->assertSame(5, $retryConfig['max_attempts']);
        $this->assertSame(120, $retryConfig['delay']);
        $this->assertFalse($retryConfig['backoff']);

        $timeoutConfig = $this->queueService->getTimeoutConfig();
        $this->assertSame(600, $timeoutConfig['job_timeout']);
        $this->assertSame(1200, $timeoutConfig['queue_timeout']);
    }

    #[Test]
    public function it_returns_default_connection_configuration(): void
    {
        Config::set('inbounder.mailgun.queue.connection', []);

        $this->assertSame('default', $this->queueService->getConnectionDriver());

        $retryConfig = $this->queueService->getRetryConfig();
        $this->assertSame(3, $retryConfig['max_attempts']);
        $this->assertSame(60, $retryConfig['delay']);
        $this->assertTrue($retryConfig['backoff']);

        $timeoutConfig = $this->queueService->getTimeoutConfig();
        $this->assertSame(300, $timeoutConfig['job_timeout']);
        $this->assertSame(600, $timeoutConfig['queue_timeout']);
    }

    #[Test]
    public function it_returns_batch_configuration(): void
    {
        Config::set('inbounder.mailgun.queue.batch', [
            'enabled' => true,
            'max_size' => 200,
            'delay' => 10,
        ]);

        $this->assertTrue($this->queueService->isBatchEnabled());
        $this->assertSame(200, $this->queueService->getMaxBatchSize());
        $this->assertSame(10, $this->queueService->getBatchDelay());
    }

    #[Test]
    public function it_returns_default_batch_configuration(): void
    {
        Config::set('inbounder.mailgun.queue.batch', []);

        $this->assertTrue($this->queueService->isBatchEnabled());
        $this->assertSame(100, $this->queueService->getMaxBatchSize());
        $this->assertSame(5, $this->queueService->getBatchDelay());
    }

    #[Test]
    public function it_returns_all_queue_names(): void
    {
        Config::set('inbounder.mailgun.queue', [
            'enabled' => true,
            'default_queue' => 'default-mailgun',
            'queues' => [
                'templated_emails' => 'email-queue',
                'webhook_events' => 'webhook-queue',
                'inbound_emails' => 'inbound-queue',
                'tracking' => 'tracking-queue',
            ],
        ]);

        $queueNames = $this->queueService->getAllQueueNames();

        $this->assertContains('default-mailgun', $queueNames);
        $this->assertContains('email-queue', $queueNames);
        $this->assertContains('webhook-queue', $queueNames);
        $this->assertContains('inbound-queue', $queueNames);
        $this->assertContains('tracking-queue', $queueNames);
        $this->assertCount(5, $queueNames);
    }

    #[Test]
    public function it_returns_single_queue_name_when_disabled(): void
    {
        Config::set('inbounder.mailgun.queue.enabled', false);

        $queueNames = $this->queueService->getAllQueueNames();

        $this->assertSame(['mailgun'], $queueNames);
    }

    #[Test]
    public function it_returns_job_configuration(): void
    {
        Config::set('inbounder.mailgun.queue', [
            'enabled' => true,
            'default_queue' => 'default-mailgun',
            'queues' => [
                'templated_emails' => 'email-queue',
            ],
            'connection' => [
                'driver' => 'redis',
                'retry' => [
                    'max_attempts' => 5,
                    'delay' => 120,
                    'backoff' => false,
                ],
                'timeout' => [
                    'job_timeout' => 600,
                    'queue_timeout' => 1200,
                ],
            ],
        ]);

        $jobConfig = $this->queueService->getJobConfig('templated_emails');

        $this->assertSame('email-queue', $jobConfig['queue']);
        $this->assertSame('redis', $jobConfig['connection']);
        $this->assertSame(5, $jobConfig['retry']['max_attempts']);
        $this->assertSame(120, $jobConfig['retry']['delay']);
        $this->assertFalse($jobConfig['retry']['backoff']);
        $this->assertSame(600, $jobConfig['timeout']['job_timeout']);
        $this->assertSame(1200, $jobConfig['timeout']['queue_timeout']);
    }

    #[Test]
    public function it_handles_missing_configuration_gracefully(): void
    {
        Config::set('inbounder.mailgun.queue', []);

        $this->assertFalse($this->queueService->isEnabled());
        $this->assertSame('mailgun', $this->queueService->getDefaultQueue());
        $this->assertSame('mailgun', $this->queueService->getTemplatedEmailsQueue());
        $this->assertSame('default', $this->queueService->getConnectionDriver());
        $this->assertTrue($this->queueService->isBatchEnabled());
        $this->assertSame(100, $this->queueService->getMaxBatchSize());
    }
}
