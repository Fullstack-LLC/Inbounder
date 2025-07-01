<?php

namespace Inbounder\Console\Commands;

use Illuminate\Console\Command;
use Inbounder\Jobs\ProcessInboundEmailsJob;

class ProcessInboundEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inbounder:process-emails {--queue= : The queue to dispatch the job to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process unprocessed inbound emails and dispatch templated emails for matching distribution lists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $queue = $this->option('queue');

        $this->info('Dispatching ProcessInboundEmailsJob...');

        if ($queue) {
            ProcessInboundEmailsJob::dispatch()->onQueue($queue);
            $this->info("Job dispatched to queue: {$queue}");
        } else {
            ProcessInboundEmailsJob::dispatch();
            $this->info('Job dispatched to default queue');
        }

        $this->info('ProcessInboundEmailsJob has been queued successfully!');

        return Command::SUCCESS;
    }
}
