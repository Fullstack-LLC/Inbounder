<?php

declare(strict_types=1);

namespace Inbounder\Tests\Feature\ConsoleCommands\DistributionLists;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbounder\Console\Commands\DistributionLists\CreateDistributionList;
use Inbounder\Models\DistributionList;
use Inbounder\Services\DistributionListService;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the CreateDistributionList command.
 */
class CreateDistributionListTest extends TestCase
{
    use RefreshDatabase;

    private CreateDistributionList $command;

    private DistributionListService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DistributionListService::class);
        $this->command = app(CreateDistributionList::class);
    }

    #[Test]
    public function it_creates_a_distribution_list_with_provided_arguments()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'Test Newsletter',
            '--description' => 'A test newsletter list',
            '--category' => 'Marketing',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Test Newsletter',
            'slug' => 'test-newsletter',
            'description' => 'A test newsletter list',
            'category' => 'Marketing',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_a_list_with_custom_slug()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'Custom List',
            '--slug' => 'custom-slug',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Custom List',
            'slug' => 'custom-slug',
        ]);
    }

    #[Test]
    public function it_creates_inactive_list_when_specified()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'Inactive List',
            '--inactive' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Inactive List',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_creates_list_in_interactive_mode()
    {
        $this->artisan('mailgun:create-list', ['--interactive' => true])
            ->expectsQuestion('List name', 'Interactive List')
            ->expectsQuestion('List description (optional)', 'Interactive description')
            ->expectsQuestion('List category (optional)', 'Interactive')
            ->expectsConfirmation('Should the list be active?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Interactive List',
            'description' => 'Interactive description',
            'category' => 'Interactive',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_handles_empty_optional_fields_in_interactive_mode()
    {
        $this->artisan('mailgun:create-list', ['--interactive' => true])
            ->expectsQuestion('List name', 'Minimal List')
            ->expectsQuestion('List description (optional)', '')
            ->expectsQuestion('List category (optional)', '')
            ->expectsConfirmation('Should the list be active?', 'yes')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Minimal List',
            'description' => null,
            'category' => null,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_creates_inactive_list_in_interactive_mode()
    {
        $this->artisan('mailgun:create-list', ['--interactive' => true])
            ->expectsQuestion('List name', 'Inactive Interactive')
            ->expectsQuestion('List description (optional)', '')
            ->expectsQuestion('List category (optional)', '')
            ->expectsConfirmation('Should the list be active?', 'no')
            ->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Inactive Interactive',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_requires_name_argument_when_not_in_interactive_mode()
    {
        $this->artisan('mailgun:create-list')
            ->expectsOutput('❌ List name is required when not in interactive mode.')
            ->assertExitCode(1);
    }

    #[Test]
    public function it_validates_name_length()
    {
        $longName = str_repeat('a', 256);

        $this->artisan('mailgun:create-list', [
            '--name' => $longName,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('distribution_lists', [
            'name' => $longName,
        ]);
    }

    #[Test]
    public function it_validates_slug_uniqueness()
    {
        // Create first list
        DistributionList::create([
            'name' => 'First List',
            'slug' => 'test-slug',
            'is_active' => true,
        ]);

        // Try to create second list with same slug
        $this->artisan('mailgun:create-list', [
            '--name' => 'Second List',
            '--slug' => 'test-slug',
        ])->assertExitCode(1);

        $this->assertDatabaseCount('distribution_lists', 1);
    }

    #[Test]
    public function it_generates_unique_slug_when_name_conflicts()
    {
        // Create first list
        DistributionList::create([
            'name' => 'Test List',
            'slug' => 'test-list',
            'is_active' => true,
        ]);

        // Create second list with same name (should generate different slug)
        $this->artisan('mailgun:create-list', [
            '--name' => 'Test List',
        ])->assertExitCode(0);

        $lists = DistributionList::where('name', 'Test List')->get();
        $this->assertCount(2, $lists);
        $this->assertNotEquals($lists[0]->slug, $lists[1]->slug);
    }

    #[Test]
    public function it_handles_special_characters_in_name_for_slug_generation()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'Special Characters & Symbols!',
        ])->assertExitCode(0);

        $this->assertDatabaseHas('distribution_lists', [
            'name' => 'Special Characters & Symbols!',
            'slug' => 'special-characters-symbols',
        ]);
    }

    #[Test]
    public function it_validates_category_length()
    {
        $longCategory = str_repeat('a', 101);

        $this->artisan('mailgun:create-list', [
            '--name' => 'Test List',
            '--category' => $longCategory,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('distribution_lists', [
            'name' => 'Test List',
        ]);
    }

    #[Test]
    public function it_validates_description_length()
    {
        $longDescription = str_repeat('a', 1001);

        $this->artisan('mailgun:create-list', [
            '--name' => 'Test List',
            '--description' => $longDescription,
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('distribution_lists', [
            'name' => 'Test List',
        ]);
    }

    #[Test]
    public function it_creates_list_with_metadata()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'List with Metadata',
            '--metadata' => '{"source": "api", "version": "1.0"}',
        ])->assertExitCode(0);

        $list = DistributionList::where('name', 'List with Metadata')->first();
        $this->assertNotNull($list);
        $this->assertEquals(['source' => 'api', 'version' => '1.0'], $list->metadata);
    }

    #[Test]
    public function it_handles_invalid_json_metadata()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'Test List',
            '--metadata' => 'invalid json',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('distribution_lists', [
            'name' => 'Test List',
        ]);
    }

    #[Test]
    public function it_shows_success_message_with_list_details()
    {
        $this->artisan('mailgun:create-list', [
            '--name' => 'Success Test List',
            '--description' => 'Test description',
            '--category' => 'Test Category',
        ])
            ->expectsOutput('✅ Distribution list created successfully!')
            ->expectsOutput('Name: Success Test List')
            ->expectsOutput('Slug: success-test-list')
            ->expectsOutput('Description: Test description')
            ->expectsOutput('Category: Test Category')
            ->expectsOutput('Status: Active')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_handles_service_exceptions_gracefully()
    {
        // Mock the service to throw an exception
        $mockService = $this->createMock(DistributionListService::class);
        $mockService->method('createList')
            ->willThrowException(new \Exception('Service error'));

        $this->app->instance(DistributionListService::class, $mockService);

        $this->artisan('mailgun:create-list', [
            '--name' => 'Error Test List',
        ])
            ->expectsOutput('❌ Failed to create distribution list: Service error')
            ->assertExitCode(1);
    }
}
