<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature\ConsoleCommands\DistributionLists;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Models\DistributionList;
use Inbounder\Services\DistributionListService;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the RemoveSubscribers command.
 */
class RemoveSubscribersTest extends TestCase
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

        // Add some test subscribers
        $this->list->addSubscriber('active1@example.com');
        $this->list->addSubscriber('active2@example.com');
        $this->list->addSubscriber('inactive@example.com', ['is_active' => false]);
    }

    #[Test]
    public function it_removes_subscribers_via_email_arguments()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--email' => ['active1@example.com', 'active2@example.com'],
            '--confirm' => true,
        ])
            ->expectsOutput('📧 Removing subscribers from list: Test List')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 2')
            ->expectsOutput('  Not found: 0')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active1@example.com',
        ]);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active2@example.com',
        ]);
    }

    #[Test]
    public function it_removes_all_subscribers()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--all' => true,
            '--confirm' => true,
        ])
            ->expectsOutput('📧 Removing subscribers from list: Test List')
            ->expectsOutput('✅ Removed 3 subscribers from \'Test List\'.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('distribution_list_subscribers', 0);
    }

    #[Test]
    public function it_removes_inactive_subscribers()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--inactive' => true,
            '--confirm' => true,
        ])
            ->expectsOutput('📧 Removing subscribers from list: Test List')
            ->expectsOutput('✅ Removed 1 inactive subscribers from \'Test List\'.')
            ->assertExitCode(0);

        // Active subscribers should remain
        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active2@example.com',
        ]);

        // Inactive subscriber should be removed
        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'inactive@example.com',
        ]);
    }

    #[Test]
    public function it_removes_subscribers_from_csv_file()
    {
        // Create a temporary CSV file
        $filePath = storage_path('app/remove_subscribers.csv');
        $csvContent = "email\n";
        $csvContent .= "active1@example.com\n";
        $csvContent .= "nonexistent@example.com\n";
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
            '--confirm' => true,
        ])
            ->expectsOutput('📧 Removing subscribers from list: Test List')
            ->expectsOutput('Found 2 email addresses in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 1')
            ->expectsOutput('  Not found: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active1@example.com',
        ]);

        unlink($filePath);
    }

    #[Test]
    public function it_handles_nonexistent_list()
    {
        $this->artisan('mailgun:remove-subscribers', [
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

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'inactive-list',
            '--email' => ['test@example.com'],
        ])
            ->expectsOutput("❌ List 'inactive-list' not found or inactive.")
            ->assertExitCode(1);
    }

    public function it_handles_nonexistent_csv_file()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => 'nonexistent.csv',
        ])
            ->expectsOutput("❌ File 'nonexistent.csv' not found.")
            ->assertExitCode(1);
    }

    public function it_handles_empty_csv_file()
    {
        $filePath = storage_path('app/empty_remove.csv');
        file_put_contents($filePath, '');

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
        ])
            ->expectsOutput('No valid email addresses found in file.')
            ->assertExitCode(0);

        unlink($filePath);
    }

    public function it_handles_csv_with_header_detection()
    {
        $filePath = storage_path('app/with_header.csv');
        $csvContent = "email\n";
        $csvContent .= "active1@example.com\n";
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
            '--confirm' => true,
        ])
            ->expectsOutput('Found 1 email addresses in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 1')
            ->assertExitCode(0);

        unlink($filePath);
    }

    public function it_handles_csv_without_header()
    {
        $filePath = storage_path('app/without_header.csv');
        $csvContent = "active1@example.com\n";
        $csvContent .= "active2@example.com\n";
        file_put_contents($filePath, $csvContent);

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
            '--confirm' => true,
        ])
            ->expectsOutput('Found 2 email addresses in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 2')
            ->assertExitCode(0);

        unlink($filePath);
    }

    public function it_handles_nonexistent_emails()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--email' => ['nonexistent@example.com', 'another@example.com'],
            '--confirm' => true,
        ])
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 0')
            ->expectsOutput('  Not found: 2')
            ->assertExitCode(0);
    }

    public function it_handles_mixed_existing_and_nonexistent_emails()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--email' => ['active1@example.com', 'nonexistent@example.com'],
            '--confirm' => true,
        ])
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 1')
            ->expectsOutput('  Not found: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active1@example.com',
        ]);
    }

    public function it_requires_confirmation_for_removing_all_subscribers()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--all' => true,
        ])
            ->expectsConfirmation('Are you sure you want to remove all 3 subscribers from \'Test List\'?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        // Subscribers should still exist
        $this->assertDatabaseCount('distribution_list_subscribers', 3);
    }

    public function it_requires_confirmation_for_removing_inactive_subscribers()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--inactive' => true,
        ])
            ->expectsConfirmation('Are you sure you want to remove 1 inactive subscribers from \'Test List\'?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        // Inactive subscriber should still exist
        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'inactive@example.com',
        ]);
    }

    public function it_requires_confirmation_for_removing_specific_emails()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--email' => ['active1@example.com', 'active2@example.com'],
        ])
            ->expectsOutput('Email addresses to remove:')
            ->expectsOutput('  - active1@example.com')
            ->expectsOutput('  - active2@example.com')
            ->expectsConfirmation('Are you sure you want to remove these subscribers from \'Test List\'?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);

        // Subscribers should still exist
        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'active2@example.com',
        ]);
    }

    public function it_handles_empty_list_for_removing_all()
    {
        $emptyList = DistributionList::create([
            'name' => 'Empty List',
            'slug' => 'empty-list',
            'is_active' => true,
        ]);

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'empty-list',
            '--all' => true,
            '--confirm' => true,
        ])
            ->expectsOutput('No subscribers to remove.')
            ->assertExitCode(0);
    }

    public function it_handles_empty_list_for_removing_inactive()
    {
        $activeOnlyList = DistributionList::create([
            'name' => 'Active Only List',
            'slug' => 'active-only',
            'is_active' => true,
        ]);

        $activeOnlyList->addSubscriber('active@example.com');

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'active-only',
            '--inactive' => true,
            '--confirm' => true,
        ])
            ->expectsOutput('No inactive subscribers to remove.')
            ->assertExitCode(0);
    }

    public function it_handles_service_exceptions()
    {
        // Mock the service to throw an exception
        $mockService = $this->createMock(DistributionListService::class);
        $mockService->method('getListBySlug')
            ->willReturn($this->list);
        $mockService->method('removeSubscribers')
            ->willThrowException(new \Exception('Service error'));

        $this->app->instance(DistributionListService::class, $mockService);

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--email' => ['test@example.com'],
            '--confirm' => true,
        ])
            ->expectsOutput('❌ Failed to remove subscribers: Service error')
            ->assertExitCode(1);
    }

    public function it_handles_csv_with_quoted_emails()
    {
        $filePath = storage_path('app/quoted_emails.csv');
        $csvContent = "\"quoted@example.com\"\n";
        $csvContent .= "unquoted@example.com\n";
        file_put_contents($filePath, $csvContent);

        // Add the quoted email to the list
        $this->list->addSubscriber('quoted@example.com');

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
            '--confirm' => true,
        ])
            ->expectsOutput('Found 2 email addresses in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 1')
            ->expectsOutput('  Not found: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'quoted@example.com',
        ]);

        unlink($filePath);
    }

    public function it_handles_csv_with_whitespace()
    {
        $filePath = storage_path('app/whitespace.csv');
        $csvContent = "  spaced@example.com  \n";
        $csvContent .= "\ttabbed@example.com\t\n";
        file_put_contents($filePath, $csvContent);

        // Add the spaced email to the list
        $this->list->addSubscriber('spaced@example.com');

        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--file' => $filePath,
            '--confirm' => true,
        ])
            ->expectsOutput('Found 2 email addresses in file.')
            ->expectsOutput('✅ Subscribers processed:')
            ->expectsOutput('  Removed: 1')
            ->expectsOutput('  Not found: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $this->list->id,
            'email' => 'spaced@example.com',
        ]);

        unlink($filePath);
    }

    public function it_handles_empty_email_array()
    {
        $this->artisan('mailgun:remove-subscribers', [
            'list' => 'test-list',
            '--email' => [],
            '--confirm' => true,
        ])
            ->expectsOutput('No email addresses to remove.')
            ->assertExitCode(0);
    }
}
