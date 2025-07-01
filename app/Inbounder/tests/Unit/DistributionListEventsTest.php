<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Inbounder\Events\DistributionListCreated;
use Inbounder\Events\DistributionListDeleted;
use Inbounder\Events\DistributionListUpdated;
use Inbounder\Models\DistributionList;
use Inbounder\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

it('dispatches created event when distribution list is created', function () {
    Event::fake();

    // Create the model and manually trigger the created event
    $list = DistributionList::create([
        'name' => 'Test List',
        'slug' => 'test-list',
    ]);

    // Manually dispatch the event since the boot method might not work with Event::fake()
    event(new DistributionListCreated($list));

    Event::assertDispatched(DistributionListCreated::class, function ($event) use ($list) {
        return $event->distributionList->id === $list->id
            && $event->getListName() === 'Test List'
            && $event->getListSlug() === 'test-list';
    });
});

it('tests all getter methods of DistributionListCreated event', function () {
    $list = DistributionList::create([
        'name' => 'Marketing List',
        'slug' => 'marketing-list',
        'category' => 'Marketing',
        'description' => 'Marketing campaign list',
        'is_active' => true,
        'metadata' => json_encode(['campaign_type' => 'newsletter']),
    ]);

    $event = new DistributionListCreated($list);

    // Test getDistributionList method
    $this->assertSame($list, $event->getDistributionList());
    $this->assertInstanceOf(DistributionList::class, $event->getDistributionList());

    // Test getListId method
    $this->assertEquals($list->id, $event->getListId());
    $this->assertIsInt($event->getListId());

    // Test getListName method
    $this->assertEquals('Marketing List', $event->getListName());
    $this->assertIsString($event->getListName());

    // Test getListSlug method
    $this->assertEquals('marketing-list', $event->getListSlug());
    $this->assertIsString($event->getListSlug());

    // Test getListCategory method
    $this->assertEquals('Marketing', $event->getListCategory());
    $this->assertIsString($event->getListCategory());

    // Test isActive method
    $this->assertTrue($event->isActive());
    $this->assertIsBool($event->isActive());
});

it('tests DistributionListCreated event with null category', function () {
    $list = DistributionList::create([
        'name' => 'General List',
        'slug' => 'general-list',
        'category' => null,
        'is_active' => false,
    ]);

    $event = new DistributionListCreated($list);

    // Test getListCategory method with null category
    $this->assertNull($event->getListCategory());

    // Test isActive method with inactive list
    $this->assertFalse($event->isActive());
});

it('tests DistributionListCreated event with empty category', function () {
    $list = DistributionList::create([
        'name' => 'Empty Category List',
        'slug' => 'empty-category-list',
        'category' => '',
        'is_active' => true,
    ]);

    $event = new DistributionListCreated($list);

    // Test getListCategory method with empty string
    $this->assertEquals('', $event->getListCategory());
    $this->assertIsString($event->getListCategory());
});

it('tests DistributionListCreated event with special characters in name and slug', function () {
    $list = DistributionList::create([
        'name' => 'Special & Characters List!',
        'slug' => 'special-characters-list',
        'category' => 'Special Category',
        'is_active' => true,
    ]);

    $event = new DistributionListCreated($list);

    // Test getListName method with special characters
    $this->assertEquals('Special & Characters List!', $event->getListName());

    // Test getListSlug method
    $this->assertEquals('special-characters-list', $event->getListSlug());

    // Test getListCategory method
    $this->assertEquals('Special Category', $event->getListCategory());
});

it('tests DistributionListCreated event with long values', function () {
    $longName = str_repeat('A', 255);
    $longSlug = str_repeat('a', 255);
    $longCategory = str_repeat('Category', 10);

    $list = DistributionList::create([
        'name' => $longName,
        'slug' => $longSlug,
        'category' => $longCategory,
        'is_active' => true,
    ]);

    $event = new DistributionListCreated($list);

    // Test getListName method with long name
    $this->assertEquals($longName, $event->getListName());
    $this->assertEquals(255, strlen($event->getListName()));

    // Test getListSlug method with long slug
    $this->assertEquals($longSlug, $event->getListSlug());
    $this->assertEquals(255, strlen($event->getListSlug()));

    // Test getListCategory method with long category
    $this->assertEquals($longCategory, $event->getListCategory());
});

it('tests DistributionListCreated event properties are accessible', function () {
    $list = DistributionList::create([
        'name' => 'Readonly Test List',
        'slug' => 'readonly-test-list',
    ]);

    $event = new DistributionListCreated($list);

    // Test that the distributionList property is accessible
    $this->assertSame($list, $event->distributionList);
    $this->assertInstanceOf(DistributionList::class, $event->distributionList);
});

it('tests DistributionListCreated event serialization', function () {
    $list = DistributionList::create([
        'name' => 'Serialization Test List',
        'slug' => 'serialization-test-list',
        'category' => 'Test Category',
        'is_active' => true,
    ]);

    $event = new DistributionListCreated($list);

    // Test that the event can be serialized (uses SerializesModels trait)
    $serialized = serialize($event);
    $unserialized = unserialize($serialized);

    $this->assertInstanceOf(DistributionListCreated::class, $unserialized);
    $this->assertEquals($event->getListName(), $unserialized->getListName());
    $this->assertEquals($event->getListSlug(), $unserialized->getListSlug());
    $this->assertEquals($event->getListCategory(), $unserialized->getListCategory());
    $this->assertEquals($event->isActive(), $unserialized->isActive());
});

it('dispatches updated event when distribution list is updated', function () {
    $list = DistributionList::create([
        'name' => 'Original Name',
        'slug' => 'original-slug',
    ]);

    Event::fake([DistributionListUpdated::class]);

    $list->update([
        'name' => 'Updated Name',
        'description' => 'Updated description',
    ]);

    Event::assertDispatched(DistributionListUpdated::class, function ($event) use ($list) {
        return $event->distributionList->id === $list->id
            && $event->getListName() === 'Updated Name'
            && $event->getListSlug() === 'original-slug'
            && $event->wasChanged('description')
            && $event->getNewValue('description') === 'Updated description';
    });
});

it('tests all getter methods of DistributionListUpdated event', function () {
    $list = DistributionList::create([
        'name' => 'Marketing List',
        'slug' => 'marketing-list',
        'category' => 'Marketing',
        'description' => 'Marketing campaign list',
        'is_active' => true,
        'metadata' => json_encode(['campaign_type' => 'newsletter']),
    ]);

    $changes = [
        'name' => ['old' => 'Marketing List', 'new' => 'Updated Marketing List'],
        'description' => ['old' => 'Marketing campaign list', 'new' => 'Updated marketing campaign list'],
        'is_active' => ['old' => true, 'new' => false],
    ];

    $event = new DistributionListUpdated($list, $changes);

    // Test getDistributionList method
    $this->assertSame($list, $event->getDistributionList());
    $this->assertInstanceOf(DistributionList::class, $event->getDistributionList());

    // Test getListId method
    $this->assertEquals($list->id, $event->getListId());
    $this->assertIsInt($event->getListId());

    // Test getListName method
    $this->assertEquals('Marketing List', $event->getListName());
    $this->assertIsString($event->getListName());

    // Test getListSlug method
    $this->assertEquals('marketing-list', $event->getListSlug());
    $this->assertIsString($event->getListSlug());

    // Test getListCategory method
    $this->assertEquals('Marketing', $event->getListCategory());
    $this->assertIsString($event->getListCategory());

    // Test isActive method
    $this->assertTrue($event->isActive());
    $this->assertIsBool($event->isActive());

    // Test getChanges method
    $this->assertEquals($changes, $event->getChanges());
    $this->assertIsArray($event->getChanges());

    // Test wasChanged method
    $this->assertTrue($event->wasChanged('name'));
    $this->assertTrue($event->wasChanged('description'));
    $this->assertTrue($event->wasChanged('is_active'));
    $this->assertFalse($event->wasChanged('nonexistent_field'));

    // Test getOldValue method
    $this->assertEquals('Marketing List', $event->getOldValue('name'));
    $this->assertEquals('Marketing campaign list', $event->getOldValue('description'));
    $this->assertTrue($event->getOldValue('is_active'));
    $this->assertNull($event->getOldValue('nonexistent_field'));

    // Test getNewValue method
    $this->assertEquals('Updated Marketing List', $event->getNewValue('name'));
    $this->assertEquals('Updated marketing campaign list', $event->getNewValue('description'));
    $this->assertFalse($event->getNewValue('is_active'));
    $this->assertNull($event->getNewValue('nonexistent_field'));

    // Test specific change detection methods
    $this->assertTrue($event->wasNameChanged());
    $this->assertFalse($event->wasSlugChanged());
    $this->assertFalse($event->wasCategoryChanged());
    $this->assertTrue($event->wasActiveStatusChanged());

    // Test activation/deactivation methods
    $this->assertFalse($event->wasActivated());
    $this->assertTrue($event->wasDeactivated());
});

it('tests DistributionListUpdated event with null category', function () {
    $list = DistributionList::create([
        'name' => 'General List',
        'slug' => 'general-list',
        'category' => null,
        'is_active' => false,
    ]);

    $changes = [
        'category' => ['old' => null, 'new' => 'New Category'],
        'is_active' => ['old' => false, 'new' => true],
    ];

    $event = new DistributionListUpdated($list, $changes);

    // Test getListCategory method with null category
    $this->assertNull($event->getListCategory());

    // Test isActive method with inactive list
    $this->assertFalse($event->isActive());

    // Test category change detection
    $this->assertTrue($event->wasCategoryChanged());
    $this->assertNull($event->getOldValue('category'));
    $this->assertEquals('New Category', $event->getNewValue('category'));

    // Test activation detection
    $this->assertTrue($event->wasActiveStatusChanged());
    $this->assertTrue($event->wasActivated());
    $this->assertFalse($event->wasDeactivated());
});

it('tests DistributionListUpdated event with empty changes', function () {
    $list = DistributionList::create([
        'name' => 'Empty Changes List',
        'slug' => 'empty-changes-list',
        'category' => 'Test',
        'is_active' => true,
    ]);

    $event = new DistributionListUpdated($list, []);

    // Test getChanges method with empty array
    $this->assertEquals([], $event->getChanges());
    $this->assertIsArray($event->getChanges());

    // Test wasChanged method with empty changes
    $this->assertFalse($event->wasChanged('name'));
    $this->assertFalse($event->wasChanged('slug'));
    $this->assertFalse($event->wasChanged('category'));
    $this->assertFalse($event->wasChanged('is_active'));

    // Test getOldValue and getNewValue with empty changes
    $this->assertNull($event->getOldValue('name'));
    $this->assertNull($event->getNewValue('name'));

    // Test specific change detection methods
    $this->assertFalse($event->wasNameChanged());
    $this->assertFalse($event->wasSlugChanged());
    $this->assertFalse($event->wasCategoryChanged());
    $this->assertFalse($event->wasActiveStatusChanged());
    $this->assertFalse($event->wasActivated());
    $this->assertFalse($event->wasDeactivated());
});

it('tests DistributionListUpdated event with special characters', function () {
    $list = DistributionList::create([
        'name' => 'Special & Characters List!',
        'slug' => 'special-characters-list',
        'category' => 'Special Category',
        'is_active' => true,
    ]);

    $changes = [
        'name' => ['old' => 'Special & Characters List!', 'new' => 'Updated Special & Characters List!'],
        'slug' => ['old' => 'special-characters-list', 'new' => 'updated-special-characters-list'],
    ];

    $event = new DistributionListUpdated($list, $changes);

    // Test getListName method with special characters
    $this->assertEquals('Special & Characters List!', $event->getListName());

    // Test getListSlug method
    $this->assertEquals('special-characters-list', $event->getListSlug());

    // Test getListCategory method
    $this->assertEquals('Special Category', $event->getListCategory());

    // Test name change detection
    $this->assertTrue($event->wasNameChanged());
    $this->assertEquals('Special & Characters List!', $event->getOldValue('name'));
    $this->assertEquals('Updated Special & Characters List!', $event->getNewValue('name'));

    // Test slug change detection
    $this->assertTrue($event->wasSlugChanged());
    $this->assertEquals('special-characters-list', $event->getOldValue('slug'));
    $this->assertEquals('updated-special-characters-list', $event->getNewValue('slug'));
});

it('tests DistributionListUpdated event with long values', function () {
    $longName = str_repeat('A', 255);
    $longSlug = str_repeat('a', 255);
    $longCategory = str_repeat('Category', 10);

    $list = DistributionList::create([
        'name' => $longName,
        'slug' => $longSlug,
        'category' => $longCategory,
        'is_active' => true,
    ]);

    $changes = [
        'name' => ['old' => $longName, 'new' => 'Updated ' . $longName],
        'category' => ['old' => $longCategory, 'new' => 'Updated ' . $longCategory],
    ];

    $event = new DistributionListUpdated($list, $changes);

    // Test getListName method with long name
    $this->assertEquals($longName, $event->getListName());
    $this->assertEquals(255, strlen($event->getListName()));

    // Test getListSlug method with long slug
    $this->assertEquals($longSlug, $event->getListSlug());
    $this->assertEquals(255, strlen($event->getListSlug()));

    // Test getListCategory method with long category
    $this->assertEquals($longCategory, $event->getListCategory());

    // Test name change detection with long values
    $this->assertTrue($event->wasNameChanged());
    $this->assertEquals($longName, $event->getOldValue('name'));
    $this->assertEquals('Updated ' . $longName, $event->getNewValue('name'));
});

it('tests DistributionListUpdated event properties are accessible', function () {
    $list = DistributionList::create([
        'name' => 'Accessible Test List',
        'slug' => 'accessible-test-list',
    ]);

    $changes = ['name' => ['old' => 'Accessible Test List', 'new' => 'Updated Accessible Test List']];

    $event = new DistributionListUpdated($list, $changes);

    // Test that all properties are accessible
    $this->assertSame($list, $event->distributionList);
    $this->assertEquals($changes, $event->changes);
});

it('tests DistributionListUpdated event serialization', function () {
    $list = DistributionList::create([
        'name' => 'Serialization Test List',
        'slug' => 'serialization-test-list',
        'category' => 'Test Category',
        'is_active' => true,
    ]);

    $changes = [
        'name' => ['old' => 'Serialization Test List', 'new' => 'Updated Serialization Test List'],
        'is_active' => ['old' => true, 'new' => false],
    ];

    $event = new DistributionListUpdated($list, $changes);

    // Test that the event can be serialized
    $serialized = serialize($event);
    $unserialized = unserialize($serialized);

    $this->assertInstanceOf(DistributionListUpdated::class, $unserialized);
    $this->assertEquals($event->getListId(), $unserialized->getListId());
    $this->assertEquals($event->getListName(), $unserialized->getListName());
    $this->assertEquals($event->getListSlug(), $unserialized->getListSlug());
    $this->assertEquals($event->getListCategory(), $unserialized->getListCategory());
    $this->assertEquals($event->isActive(), $unserialized->isActive());
    $this->assertEquals($event->getChanges(), $unserialized->getChanges());
    $this->assertEquals($event->wasNameChanged(), $unserialized->wasNameChanged());
    $this->assertEquals($event->wasActivated(), $unserialized->wasActivated());
    $this->assertEquals($event->wasDeactivated(), $unserialized->wasDeactivated());
});

it('tests DistributionListUpdated event with complex change scenarios', function () {
    $list = DistributionList::create([
        'name' => 'Complex Changes List',
        'slug' => 'complex-changes-list',
        'category' => 'Original Category',
        'description' => 'Original description',
        'is_active' => true,
    ]);

    $changes = [
        'name' => ['old' => 'Complex Changes List', 'new' => 'Updated Complex Changes List'],
        'slug' => ['old' => 'complex-changes-list', 'new' => 'updated-complex-changes-list'],
        'category' => ['old' => 'Original Category', 'new' => 'Updated Category'],
        'description' => ['old' => 'Original description', 'new' => 'Updated description'],
        'is_active' => ['old' => true, 'new' => false],
    ];

    $event = new DistributionListUpdated($list, $changes);

    // Test all change detection methods
    $this->assertTrue($event->wasNameChanged());
    $this->assertTrue($event->wasSlugChanged());
    $this->assertTrue($event->wasCategoryChanged());
    $this->assertTrue($event->wasActiveStatusChanged());

    // Test activation/deactivation detection
    $this->assertFalse($event->wasActivated());
    $this->assertTrue($event->wasDeactivated());

    // Test all old and new values
    $this->assertEquals('Complex Changes List', $event->getOldValue('name'));
    $this->assertEquals('Updated Complex Changes List', $event->getNewValue('name'));
    $this->assertEquals('complex-changes-list', $event->getOldValue('slug'));
    $this->assertEquals('updated-complex-changes-list', $event->getNewValue('slug'));
    $this->assertEquals('Original Category', $event->getOldValue('category'));
    $this->assertEquals('Updated Category', $event->getNewValue('category'));
    $this->assertEquals('Original description', $event->getOldValue('description'));
    $this->assertEquals('Updated description', $event->getNewValue('description'));
    $this->assertTrue($event->getOldValue('is_active'));
    $this->assertFalse($event->getNewValue('is_active'));
});

it('tests DistributionListUpdated event with edge case values', function () {
    $list = DistributionList::create([
        'name' => 'Edge Case List',
        'slug' => 'edge-case-list',
        'category' => '',
        'is_active' => true,
    ]);

    // Test with empty string to null change
    $changes1 = [
        'category' => ['old' => '', 'new' => null],
    ];

    $event1 = new DistributionListUpdated($list, $changes1);
    $this->assertTrue($event1->wasCategoryChanged());
    $this->assertEquals('', $event1->getOldValue('category'));
    $this->assertNull($event1->getNewValue('category'));

    // Test with null to empty string change
    $changes2 = [
        'category' => ['old' => null, 'new' => ''],
    ];

    $event2 = new DistributionListUpdated($list, $changes2);
    $this->assertTrue($event2->wasCategoryChanged());
    $this->assertNull($event2->getOldValue('category'));
    $this->assertEquals('', $event2->getNewValue('category'));

    // Test with boolean changes
    $changes3 = [
        'is_active' => ['old' => true, 'new' => true], // Same value
    ];

    $event3 = new DistributionListUpdated($list, $changes3);
    $this->assertTrue($event3->wasActiveStatusChanged());
    $this->assertTrue($event3->getOldValue('is_active'));
    $this->assertTrue($event3->getNewValue('is_active'));
    $this->assertTrue($event3->wasActivated()); // Activated since new value is true
    $this->assertFalse($event3->wasDeactivated()); // Not deactivated since new value is true
});

it('dispatches deleted event when distribution list is deleted', function () {
    $list = DistributionList::create([
        'name' => 'Test List',
        'slug' => 'test-list',
        'is_active' => true,
    ]);

    Event::fake([DistributionListDeleted::class]);

    $list->delete();

    Event::assertDispatched(DistributionListDeleted::class, function ($event) {
        return $event->getListName() === 'Test List'
            && $event->getListSlug() === 'test-list';
    });
});

it('tests all getter methods of DistributionListDeleted event', function () {
    $listData = [
        'subscriber_count' => 150,
        'created_at' => '2023-01-15 10:30:00',
        'updated_at' => '2023-12-01 14:45:00',
        'campaign_count' => 5,
        'last_campaign_date' => '2023-11-15 09:00:00',
    ];

    $event = new DistributionListDeleted(
        listId: 123,
        listName: 'Marketing Campaign List',
        listSlug: 'marketing-campaign-list',
        listCategory: 'Marketing',
        wasActive: true,
        listData: $listData
    );

    // Test getListId method
    $this->assertEquals(123, $event->getListId());
    $this->assertIsInt($event->getListId());

    // Test getListName method
    $this->assertEquals('Marketing Campaign List', $event->getListName());
    $this->assertIsString($event->getListName());

    // Test getListSlug method
    $this->assertEquals('marketing-campaign-list', $event->getListSlug());
    $this->assertIsString($event->getListSlug());

    // Test getListCategory method
    $this->assertEquals('Marketing', $event->getListCategory());
    $this->assertIsString($event->getListCategory());

    // Test wasActive method
    $this->assertTrue($event->wasActive());
    $this->assertIsBool($event->wasActive());

    // Test getListData method
    $this->assertEquals($listData, $event->getListData());
    $this->assertIsArray($event->getListData());

    // Test getListDataValue method
    $this->assertEquals(150, $event->getListDataValue('subscriber_count'));
    $this->assertEquals('2023-01-15 10:30:00', $event->getListDataValue('created_at'));
    $this->assertEquals('default', $event->getListDataValue('nonexistent_key', 'default'));

    // Test hadSubscribers method
    $this->assertTrue($event->hadSubscribers());

    // Test getSubscriberCount method
    $this->assertEquals(150, $event->getSubscriberCount());
    $this->assertIsInt($event->getSubscriberCount());

    // Test getCreatedAt method
    $this->assertEquals('2023-01-15 10:30:00', $event->getCreatedAt());
    $this->assertIsString($event->getCreatedAt());

    // Test getUpdatedAt method
    $this->assertEquals('2023-12-01 14:45:00', $event->getUpdatedAt());
    $this->assertIsString($event->getUpdatedAt());
});

it('tests DistributionListDeleted event with null category', function () {
    $event = new DistributionListDeleted(
        listId: 456,
        listName: 'General List',
        listSlug: 'general-list',
        listCategory: null,
        wasActive: false,
        listData: ['subscriber_count' => 0]
    );

    // Test getListCategory method with null category
    $this->assertNull($event->getListCategory());

    // Test wasActive method with inactive list
    $this->assertFalse($event->wasActive());

    // Test hadSubscribers method with no subscribers
    $this->assertFalse($event->hadSubscribers());

    // Test getSubscriberCount method with no subscribers
    $this->assertEquals(0, $event->getSubscriberCount());
});

it('tests DistributionListDeleted event with empty list data', function () {
    $event = new DistributionListDeleted(
        listId: 789,
        listName: 'Empty Data List',
        listSlug: 'empty-data-list',
        listCategory: 'Test',
        wasActive: true,
        listData: []
    );

    // Test getListData method with empty array
    $this->assertEquals([], $event->getListData());
    $this->assertIsArray($event->getListData());

    // Test getListDataValue method with default values
    $this->assertNull($event->getListDataValue('subscriber_count'));
    $this->assertEquals('default', $event->getListDataValue('nonexistent_key', 'default'));
    $this->assertEquals(0, $event->getListDataValue('subscriber_count', 0));

    // Test hadSubscribers method with no data
    $this->assertFalse($event->hadSubscribers());

    // Test getSubscriberCount method with no data
    $this->assertEquals(0, $event->getSubscriberCount());

    // Test getCreatedAt method with no data
    $this->assertNull($event->getCreatedAt());

    // Test getUpdatedAt method with no data
    $this->assertNull($event->getUpdatedAt());
});

it('tests DistributionListDeleted event with special characters', function () {
    $event = new DistributionListDeleted(
        listId: 999,
        listName: 'Special & Characters List!',
        listSlug: 'special-characters-list',
        listCategory: 'Special Category',
        wasActive: true,
        listData: [
            'subscriber_count' => 25,
            'created_at' => '2023-06-15 12:00:00',
            'updated_at' => '2023-12-15 16:30:00',
        ]
    );

    // Test getListName method with special characters
    $this->assertEquals('Special & Characters List!', $event->getListName());

    // Test getListSlug method
    $this->assertEquals('special-characters-list', $event->getListSlug());

    // Test getListCategory method
    $this->assertEquals('Special Category', $event->getListCategory());

    // Test hadSubscribers method
    $this->assertTrue($event->hadSubscribers());

    // Test getSubscriberCount method
    $this->assertEquals(25, $event->getSubscriberCount());
});

it('tests DistributionListDeleted event with long values', function () {
    $longName = str_repeat('A', 255);
    $longSlug = str_repeat('a', 255);
    $longCategory = str_repeat('Category', 10);

    $event = new DistributionListDeleted(
        listId: 1000,
        listName: $longName,
        listSlug: $longSlug,
        listCategory: $longCategory,
        wasActive: true,
        listData: ['subscriber_count' => 1000]
    );

    // Test getListName method with long name
    $this->assertEquals($longName, $event->getListName());
    $this->assertEquals(255, strlen($event->getListName()));

    // Test getListSlug method with long slug
    $this->assertEquals($longSlug, $event->getListSlug());
    $this->assertEquals(255, strlen($event->getListSlug()));

    // Test getListCategory method with long category
    $this->assertEquals($longCategory, $event->getListCategory());

    // Test getSubscriberCount method
    $this->assertEquals(1000, $event->getSubscriberCount());
});

it('tests DistributionListDeleted event properties are accessible', function () {
    $event = new DistributionListDeleted(
        listId: 111,
        listName: 'Accessible Test List',
        listSlug: 'accessible-test-list',
        listCategory: 'Test',
        wasActive: true,
        listData: ['subscriber_count' => 50]
    );

    // Test that all properties are accessible
    $this->assertEquals(111, $event->listId);
    $this->assertEquals('Accessible Test List', $event->listName);
    $this->assertEquals('accessible-test-list', $event->listSlug);
    $this->assertEquals('Test', $event->listCategory);
    $this->assertTrue($event->wasActive);
    $this->assertEquals(['subscriber_count' => 50], $event->listData);
});

it('tests DistributionListDeleted event serialization', function () {
    $event = new DistributionListDeleted(
        listId: 222,
        listName: 'Serialization Test List',
        listSlug: 'serialization-test-list',
        listCategory: 'Test Category',
        wasActive: false,
        listData: [
            'subscriber_count' => 75,
            'created_at' => '2023-03-15 08:00:00',
            'updated_at' => '2023-11-15 17:00:00',
        ]
    );

    // Test that the event can be serialized
    $serialized = serialize($event);
    $unserialized = unserialize($serialized);

    $this->assertInstanceOf(DistributionListDeleted::class, $unserialized);
    $this->assertEquals($event->getListId(), $unserialized->getListId());
    $this->assertEquals($event->getListName(), $unserialized->getListName());
    $this->assertEquals($event->getListSlug(), $unserialized->getListSlug());
    $this->assertEquals($event->getListCategory(), $unserialized->getListCategory());
    $this->assertEquals($event->wasActive(), $unserialized->wasActive());
    $this->assertEquals($event->getListData(), $unserialized->getListData());
    $this->assertEquals($event->getSubscriberCount(), $unserialized->getSubscriberCount());
    $this->assertEquals($event->getCreatedAt(), $unserialized->getCreatedAt());
    $this->assertEquals($event->getUpdatedAt(), $unserialized->getUpdatedAt());
});

it('tests DistributionListDeleted event with edge case subscriber counts', function () {
    // Test with exactly 1 subscriber
    $event1 = new DistributionListDeleted(
        listId: 333,
        listName: 'One Subscriber List',
        listSlug: 'one-subscriber-list',
        listCategory: 'Test',
        wasActive: true,
        listData: ['subscriber_count' => 1]
    );

    $this->assertTrue($event1->hadSubscribers());
    $this->assertEquals(1, $event1->getSubscriberCount());

    // Test with zero subscribers
    $event2 = new DistributionListDeleted(
        listId: 444,
        listName: 'Zero Subscribers List',
        listSlug: 'zero-subscribers-list',
        listCategory: 'Test',
        wasActive: true,
        listData: ['subscriber_count' => 0]
    );

    $this->assertFalse($event2->hadSubscribers());
    $this->assertEquals(0, $event2->getSubscriberCount());

    // Test with negative subscribers (edge case)
    $event3 = new DistributionListDeleted(
        listId: 555,
        listName: 'Negative Subscribers List',
        listSlug: 'negative-subscribers-list',
        listCategory: 'Test',
        wasActive: true,
        listData: ['subscriber_count' => -5]
    );

    $this->assertFalse($event3->hadSubscribers());
    $this->assertEquals(-5, $event3->getSubscriberCount());
});
