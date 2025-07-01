<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature\ConsoleCommands\DistributionLists;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Models\DistributionList;
use Inbounder\Services\DistributionListService;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the AddSubscribers command.
 */
class AddSubscribersTest extends TestCase
{
    use RefreshDatabase;

    private DistributionListService $service;

    private DistributionList $list;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DistributionListService::class);

        $this->list = DistributionList::create([
            'name' => 'Test List',
            'slug' => 'test-list',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_adds_subscribers_via_email_arguments()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--email' => ['test1@example.com', 'test2@example.com'],
        ])
            ->expectsOutput('📧 Adding subscribers to list: Test List')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 2')
            ->expectsOutput('  Updated: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'test1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'test2@example.com',
        ]);
    }

    #[Test]
    public function it_adds_subscribers_interactively()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--interactive' => true,
        ])
            ->expectsQuestion('Email address', 'interactive@example.com')
            ->expectsQuestion('Email address', '') // End input
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->expectsOutput('  Updated: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'interactive@example.com',
        ]);
    }

    #[Test]
    public function it_adds_subscribers_from_csv_file()
    {
        // Create a temporary CSV file
        $csvContent = "email\n";
        $csvContent .= "csv1@example.com\n";
        $csvContent .= "csv2@example.com\n";

        $filePath = storage_path('app/test_subscribers.csv');
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('📧 Adding subscribers to list: Test List')
            ->expectsOutput('Found 2 subscribers in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 2')
            ->expectsOutput('  Updated: 0')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'csv1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'csv2@example.com',
        ]);

        // Clean up
        unlink($filePath);
    }

    public function it_updates_existing_subscribers()
    {
        // Add initial subscriber
        $this->list->addSubscriber('existing@example.com');

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--email' => ['existing@example.com'],
        ])
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 0')
            ->expectsOutput('  Updated: 1')
            ->assertExitCode(0);

        // Verify subscriber is still active
        $subscriber = $this->list->subscribers()->where('email', 'existing@example.com')->first();
        $this->assertTrue($subscriber->is_active);
    }

    public function it_handles_nonexistent_list()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'nonexistent',
            '--email' => ['test@example.com'],
        ])
            ->expectsOutput("❌ List 'nonexistent' not found or inactive.")
            ->assertExitCode(1);
    }

    public function it_handles_inactive_list()
    {
        $inactiveList = DistributionList::create([
            'name' => 'Inactive List',
            'slug' => 'inactive-list',
            'is_active' => false,
        ]);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'inactive-list',
            '--email' => ['test@example.com'],
        ])
            ->expectsOutput("❌ List 'inactive-list' not found or inactive.")
            ->assertExitCode(1);
    }

    public function it_handles_missing_options()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
        ])
            ->expectsOutput('❌ Please specify --email, --file, or --interactive option.')
            ->assertExitCode(1);
    }

    public function it_handles_nonexistent_csv_file()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => 'nonexistent.csv',
        ])
            ->expectsOutput("❌ File 'nonexistent.csv' not found.")
            ->assertExitCode(1);
    }

    public function it_handles_empty_csv_file()
    {
        $filePath = storage_path('app/empty.csv');
        file_put_contents($filePath, '');

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('No valid subscribers found in file.')
            ->assertExitCode(0);

        unlink($filePath);
    }

    public function it_handles_csv_with_only_header()
    {
        $filePath = storage_path('app/header_only.csv');
        file_put_contents($filePath, "email\n");

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('No valid subscribers found in file.')
            ->assertExitCode(0);

        unlink($filePath);
    }

    public function it_handles_csv_with_missing_columns()
    {
        $filePath = storage_path('app/missing_columns.csv');
        file_put_contents($filePath, "email\n");
        file_put_contents($filePath, "test@example.com\n", FILE_APPEND);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('Found 1 subscribers in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'test@example.com',
        ]);

        unlink($filePath);
    }

    public function it_handles_invalid_email_addresses()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--email' => ['invalid-email', 'valid@example.com'],
        ])
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->expectsOutput('  Updated: 0')
            ->expectsOutput('⚠️  Errors encountered:')
            ->expectsOutput('  - invalid-email: The email field must be a valid email address.')
            ->assertExitCode(0);

        // Only valid email should be added
        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'valid@example.com',
        ]);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'invalid-email',
        ]);
    }

    public function it_handles_duplicate_emails_in_csv()
    {
        $filePath = storage_path('app/duplicates.csv');
        $csvContent = "email\n";
        $csvContent .= "duplicate@example.com\n";
        $csvContent .= "duplicate@example.com\n";
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('Found 2 subscribers in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->expectsOutput('  Updated: 1')
            ->assertExitCode(0);

        // Should have only one subscriber
        $subscriber = $this->list->subscribers()->where('email', 'duplicate@example.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertTrue($subscriber->is_active);

        unlink($filePath);
    }

    public function it_handles_service_exceptions()
    {
        // Mock the service to throw an exception
        $mockService = $this->createMock(DistributionListService::class);
        $mockService->method('getListBySlug')
            ->willReturn($this->list);
        $mockService->method('addSubscribers')
            ->willThrowException(new \Exception('Service error'));

        $this->app->instance(DistributionListService::class, $mockService);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--email' => ['test@example.com'],
        ])
            ->expectsOutput('❌ Failed to add subscribers: Service error')
            ->assertExitCode(1);
    }

    public function it_handles_empty_interactive_input()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--interactive' => true,
        ])
            ->expectsQuestion('Email address', '') // Empty input
            ->expectsOutput('No subscribers to add.')
            ->assertExitCode(0);
    }

    public function it_handles_optional_fields_in_interactive_mode()
    {
        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--interactive' => true,
        ])
            ->expectsQuestion('Email address', 'optional@example.com')
            ->expectsQuestion('Email address', '') // End input
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->assertExitCode(0);

        $subscriber = $this->list->subscribers()->where('email', 'optional@example.com')->first();
        $this->assertNotNull($subscriber);
        $this->assertTrue($subscriber->is_active);
    }

    public function it_handles_csv_with_extra_columns()
    {
        $filePath = storage_path('app/extra_columns.csv');
        $csvContent = "email,extra_column\n";
        $csvContent .= "extra@example.com,ExtraValue\n";
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('Found 1 subscribers in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'extra@example.com',
        ]);

        unlink($filePath);
    }

    public function it_handles_csv_with_quoted_values()
    {
        $filePath = storage_path('app/quoted.csv');
        $csvContent = "email\n";
        $csvContent .= "\"quoted@example.com\"\n";
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:add-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('Found 1 subscribers in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Added: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'quoted@example.com',
        ]);

        unlink($filePath);
    }
}
