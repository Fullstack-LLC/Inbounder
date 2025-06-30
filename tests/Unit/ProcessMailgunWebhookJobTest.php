<?php

namespace Fullstack\Inbounder\Tests\Unit;

use Fullstack\Inbounder\ProcessMailgunWebhookJob;
use Fullstack\Inbounder\Tests\TestCase;
use Fullstack\Inbounder\Tests\Unit\DummyJob;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessMailgunWebhookJobTest extends TestCase
{
    /** @var \Fullstack\Inbounder\ProcessMailgunWebhookJob */
    public $processMailgunWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['inbounder.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name' => 'mailgun',
            'payload' => [
                'event-data' => [
                    'event' => 'my.type',
                    'key' => 'value',
                ],
            ],
            'url' => '/webhooks/mailgun.com',
        ]);

        $this->processMailgunWebhookJob = new ProcessMailgunWebhookJob($this->webhookCall);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processMailgunWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['inbounder.jobs' => ['another_type' => DummyJob::class]]);

        $this->processMailgunWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['inbounder.jobs' => []]);

        $this->processMailgunWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['inbounder.jobs' => ['another_type' => DummyJob::class]]);

        $this->processMailgunWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("inbounder::{$webhookCall->payload['event-data']['event']}");

        $this->assertNull(cache('dummyjob'));
    }
}
