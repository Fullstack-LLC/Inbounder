<?php

declare(strict_types=1);

namespace Inbounder\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Inbounder\Models\DistributionList;
use Inbounder\Services\DistributionListService;
use Inbounder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Unit tests for the DistributionListService.
 */
class DistributionListServiceTest extends TestCase
{
    use RefreshDatabase;

    private DistributionListService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DistributionListService::class);
    }

    #[Test]
    public function it_creates_a_distribution_list()
    {
        $data = [
            'name' => 'Test List',
            'description' => 'A test list',
            'category' => 'Test Category',
            'is_active' => true,
        ];

        $list = $this->service->createList($data);

        $this->assertInstanceOf(DistributionList::class, $list);
        $this->assertEquals('Test List', $list->name);
        $this->assertEquals('test-list', $list->slug);
        $this->assertEquals('A test list', $list->description);
        $this->assertEquals('Test Category', $list->category);
        $this->assertTrue($list->is_active);
    }

    #[Test]
    public function it_generates_slug_from_name()
    {
        $data = ['name' => 'My Test List'];

        $list = $this->service->createList($data);

        $this->assertEquals('my-test-list', $list->slug);
    }

    #[Test]
    public function it_uses_custom_slug_when_provided()
    {
        $data = [
            'name' => 'Test List',
            'slug' => 'custom-slug',
        ];

        $list = $this->service->createList($data);

        $this->assertEquals('custom-slug', $list->slug);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $this->expectException(ValidationException::class);

        $this->service->createList([]);
    }

    #[Test]
    public function it_validates_name_length()
    {
        $this->expectException(ValidationException::class);

        $data = ['name' => str_repeat('a', 256)];
        $this->service->createList($data);
    }

    #[Test]
    public function it_validates_slug_uniqueness()
    {
        // Create first list
        $this->service->createList([
            'name' => 'First List',
            'slug' => 'test-slug',
        ]);

        // Try to create second list with same slug
        $this->expectException(ValidationException::class);

        $this->service->createList([
            'name' => 'Second List',
            'slug' => 'test-slug',
        ]);
    }

    #[Test]
    public function it_updates_a_distribution_list()
    {
        $list = $this->service->createList([
            'name' => 'Original Name',
            'description' => 'Original description',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'category' => 'New Category',
        ];

        $updatedList = $this->service->updateList($list, $updateData);

        $this->assertEquals('Updated Name', $updatedList->name);
        $this->assertEquals('Updated description', $updatedList->description);
        $this->assertEquals('New Category', $updatedList->category);
    }

    #[Test]
    public function it_gets_list_by_slug()
    {
        $list = $this->service->createList([
            'name' => 'Test List',
            'slug' => 'test-slug',
        ]);

        $foundList = $this->service->getListBySlug('test-slug');

        $this->assertNotNull($foundList);
        $this->assertEquals($list->id, $foundList->id);
    }

    #[Test]
    public function it_returns_null_for_nonexistent_slug()
    {
        $foundList = $this->service->getListBySlug('nonexistent');

        $this->assertNull($foundList);
    }

    #[Test]
    public function it_returns_null_for_inactive_list()
    {
        $list = $this->service->createList([
            'name' => 'Inactive List',
            'slug' => 'inactive-list',
            'is_active' => false,
        ]);

        $foundList = $this->service->getListBySlug('inactive-list');

        $this->assertNull($foundList);
    }

    #[Test]
    public function it_gets_active_lists()
    {
        $this->service->createList(['name' => 'Active List 1']);
        $this->service->createList(['name' => 'Active List 2']);
        $this->service->createList([
            'name' => 'Inactive List',
            'is_active' => false,
        ]);

        $activeLists = $this->service->getActiveLists();

        $this->assertCount(2, $activeLists);
        $this->assertTrue($activeLists->every(fn ($list) => $list->is_active));
    }

    #[Test]
    public function it_filters_active_lists_by_category()
    {
        $this->service->createList([
            'name' => 'Marketing List',
            'category' => 'Marketing',
        ]);
        $this->service->createList([
            'name' => 'Product List',
            'category' => 'Product',
        ]);

        $marketingLists = $this->service->getActiveLists('Marketing');

        $this->assertCount(1, $marketingLists);
        $this->assertEquals('Marketing', $marketingLists->first()->category);
    }

    #[Test]
    public function it_adds_subscribers_to_list()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $subscribers = [
            [
                'email' => 'test1@example.com',
            ],
            [
                'email' => 'test2@example.com',
            ],
        ];

        $results = $this->service->addSubscribers($list, $subscribers);

        $this->assertEquals(2, $results['added']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEmpty($results['errors']);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'test1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'test2@example.com',
        ]);
    }

    #[Test]
    public function it_updates_existing_subscribers()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        // Add initial subscriber
        $list->addSubscriber('existing@example.com');

        $subscribers = [
            [
                'email' => 'existing@example.com',
            ],
        ];

        $results = $this->service->addSubscribers($list, $subscribers);

        $this->assertEquals(0, $results['added']);
        $this->assertEquals(1, $results['updated']);
        $this->assertEmpty($results['errors']);

        $subscriber = $list->subscribers()->where('email', 'existing@example.com')->first();
        $this->assertTrue($subscriber->is_active);
    }

    #[Test]
    public function it_handles_invalid_subscriber_data()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $subscribers = [
            [
                'email' => 'invalid-email',
            ],
            [
                'email' => 'valid@example.com',
            ],
        ];

        $results = $this->service->addSubscribers($list, $subscribers);

        $this->assertEquals(1, $results['added']);
        $this->assertEquals(0, $results['updated']);
        $this->assertCount(1, $results['errors']);
        $this->assertEquals('invalid-email', $results['errors'][0]['email']);
    }

    #[Test]
    public function it_removes_subscribers_from_list()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $list->addSubscriber('test1@example.com');
        $list->addSubscriber('test2@example.com');

        $emails = ['test1@example.com', 'nonexistent@example.com'];

        $results = $this->service->removeSubscribers($list, $emails);

        $this->assertEquals(1, $results['removed']);
        $this->assertEquals(1, $results['not_found']);

        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'test1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'test2@example.com',
        ]);
    }

    #[Test]
    public function it_removes_all_subscribers_from_list()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $list->addSubscriber('test1@example.com');
        $list->addSubscriber('test2@example.com');

        $removed = $this->service->removeAllSubscribers($list);

        $this->assertEquals(2, $removed);
        $this->assertDatabaseCount('distribution_list_subscribers', 0);
    }

    #[Test]
    public function it_removes_inactive_subscribers_from_list()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $list->addSubscriber('active1@example.com');
        $list->addSubscriber('active2@example.com');
        $list->addSubscriber('inactive@example.com', ['is_active' => false]);

        $removed = $this->service->removeInactiveSubscribers($list);

        $this->assertEquals(1, $removed);

        // Active subscribers should remain
        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'active1@example.com',
        ]);

        $this->assertDatabaseHas('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'active2@example.com',
        ]);

        // Inactive subscriber should be removed
        $this->assertDatabaseMissing('distribution_list_subscribers', [
            'distribution_list_id' => $list->id,
            'email' => 'inactive@example.com',
        ]);
    }

    #[Test]
    public function it_handles_errors_in_remove_subscribers()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $list->addSubscriber('test@example.com');

        // Mock the list to throw an exception
        $mockList = $this->createMock(DistributionList::class);
        $mockList->method('removeSubscriber')
            ->willThrowException(new \Exception('Database error'));

        $emails = ['test@example.com'];

        $results = $this->service->removeSubscribers($mockList, $emails);

        $this->assertEquals(0, $results['removed']);
        $this->assertEquals(0, $results['not_found']);
        $this->assertCount(1, $results['errors']);
        $this->assertEquals('test@example.com', $results['errors'][0]['email']);
        $this->assertEquals('Database error', $results['errors'][0]['error']);
    }

    #[Test]
    public function it_gets_subscribers_for_list()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $list->addSubscriber('active@example.com');
        $list->addSubscriber('inactive@example.com', ['is_active' => false]);

        $activeSubscribers = $this->service->getSubscribers($list, true);
        $allSubscribers = $this->service->getSubscribers($list, false);

        $this->assertCount(1, $activeSubscribers);
        $this->assertCount(2, $allSubscribers);
    }

    #[Test]
    public function it_sends_campaign_to_list()
    {
        $list = $this->service->createList(['name' => 'Test List']);
        $list->addSubscriber('test@example.com');

        $results = $this->service->sendCampaignToList(
            $list,
            'test-template',
            ['name' => 'Test'],
            ['dry_run' => true]
        );

        $this->assertIsArray($results);
        $this->assertArrayHasKey('total_subscribers', $results);
        $this->assertArrayHasKey('emails_sent', $results);
        $this->assertArrayHasKey('emails_failed', $results);
    }

    #[Test]
    public function it_gets_list_statistics()
    {
        $this->service->createList([
            'name' => 'Marketing List',
            'category' => 'Marketing',
        ]);
        $this->service->createList([
            'name' => 'Product List',
            'category' => 'Product',
        ]);
        $this->service->createList([
            'name' => 'Inactive List',
            'category' => 'Marketing',
            'is_active' => false,
        ]);

        $stats = $this->service->getListStats();

        $this->assertEquals(3, $stats['total_lists']);
        $this->assertEquals(2, $stats['active_lists']);
        $this->assertEquals(1, $stats['inactive_lists']);
        $this->assertEquals(2, $stats['categories']);
        $this->assertContains('Marketing', $stats['category_list']);
        $this->assertContains('Product', $stats['category_list']);
    }

    #[Test]
    public function it_handles_empty_list_statistics()
    {
        $stats = $this->service->getListStats();

        $this->assertEquals(0, $stats['total_lists']);
        $this->assertEquals(0, $stats['active_lists']);
        $this->assertEquals(0, $stats['inactive_lists']);
        $this->assertEquals(0, $stats['categories']);
        $this->assertEmpty($stats['category_list']);
    }

    #[Test]
    public function it_validates_subscriber_data()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $results = $this->service->addSubscribers(
            $list,
            [['email' => 'invalid-email']]
        );

        $this->assertEquals(0, $results['added']);
        $this->assertEquals(0, $results['updated']);
        $this->assertCount(1, $results['errors']);
        $this->assertEquals('invalid-email', $results['errors'][0]['email']);
        $this->assertStringContainsString('email', $results['errors'][0]['error']);
    }

    #[Test]
    public function it_handles_empty_subscriber_array()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $results = $this->service->addSubscribers($list, []);

        $this->assertEquals(0, $results['added']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEmpty($results['errors']);
    }

    #[Test]
    public function it_handles_empty_email_array_for_removal()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $results = $this->service->removeSubscribers($list, []);

        $this->assertEquals(0, $results['removed']);
        $this->assertEquals(0, $results['not_found']);
    }

    #[Test]
    public function it_handles_empty_list_for_removing_all()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $removed = $this->service->removeAllSubscribers($list);

        $this->assertEquals(0, $removed);
    }

    #[Test]
    public function it_handles_empty_list_for_removing_inactive()
    {
        $list = $this->service->createList(['name' => 'Test List']);

        $removed = $this->service->removeInactiveSubscribers($list);

        $this->assertEquals(0, $removed);
    }
}
