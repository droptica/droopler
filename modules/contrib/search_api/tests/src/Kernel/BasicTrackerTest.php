<?php

namespace Drupal\Tests\search_api\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Transaction;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility\Utility;
use Psr\Log\LoggerInterface;

/**
 * Tests the "default" tracker plugin.
 *
 * @group search_api
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\search_api\tracker\Basic
 */
class BasicTrackerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'user',
    'search_api',
    'system',
  ];

  /**
   * The tracker plugin used for this test.
   *
   * @var \Drupal\search_api\Plugin\search_api\tracker\Basic
   */
  protected $tracker;

  /**
   * The search index used for this test.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The test time service used for this test.
   *
   * @var \Drupal\Tests\search_api\Kernel\TestTimeService
   */
  protected $timeService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installConfig('search_api');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $this->index = Index::create([
      'id' => 'index',
      'tracker_settings' => [
        'default' => [],
      ],
    ]);
    $this->tracker = $this->index->getTrackerInstance();
    $this->timeService = new TestTimeService();
    $this->tracker->setTimeService($this->timeService);
  }

  /**
   * Tests tracking.
   *
   * @param string $indexing_order
   *   The indexing order setting to use – "fifo" or "lifo".
   *
   * @dataProvider trackingDataProvider
   */
  public function testTracking($indexing_order) {
    // Add a logger that throws an exception when used, so a caught exception
    // within any of the tracker methods will still cause a test fail.
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
    $logger = $this->createMock(LoggerInterface::class);
    $logger->method('log')
      ->willReturnCallback(function ($level, $message, array $variables) {
        $error = 'Tracking operation threw ';
        $error .= strtr($message, $variables);
        throw new \Exception($error);
      });
    $this->tracker->setLogger($logger);

    $this->tracker->setConfiguration(['indexing_order' => $indexing_order]);
    $datasource_1 = 'test1';
    $datasource_2 = 'test2';
    $ids = [];
    foreach ([$datasource_1, $datasource_2] as $num => $datasource_id) {
      foreach ([1, 2, 3] as $raw_id) {
        $ids[$num][] = Utility::createCombinedId($datasource_id, $raw_id);
      }
    }

    // Make sure we start from a "clean slate".
    $this->assertIndexingStatus(0, 0);
    $this->assertIndexingStatus(0, 0, $datasource_1);
    $this->assertIndexingStatus(0, 0, $datasource_2);

    // Make sure tracking items as deleted, updated or indexed has no effect if
    // none were inserted before.
    $this->tracker->trackItemsDeleted([$ids[0][0]]);
    $this->assertIndexingStatus(0, 0);
    $this->tracker->trackItemsUpdated([$ids[0][0]]);
    $this->assertIndexingStatus(0, 0);
    $this->tracker->trackItemsIndexed([$ids[0][0]]);
    $this->assertIndexingStatus(0, 0);

    // Now, finally, actually do something sensible and insert some items.
    $this->tracker->trackItemsInserted([$ids[0][0]]);
    $this->tracker->trackItemsInserted([$ids[1][1]]);
    $this->timeService->advanceTime();
    $this->tracker->trackItemsInserted([$ids[0][2], $ids[1][0]]);
    $this->timeService->advanceTime();
    // Make sure re-inserting an item doesn't cause problems.
    $this->tracker->trackItemsInserted([$ids[0][1], $ids[1][2], $ids[0][0]]);
    $this->timeService->advanceTime();

    $this->assertIndexingStatus(0, 6);
    $this->assertIndexingStatus(0, 3, $datasource_1);
    $this->assertIndexingStatus(0, 3, $datasource_2);

    // Make sure the remaining items are returned as expected.
    $fifo = $indexing_order === 'fifo';
    $to_index = $this->tracker->getRemainingItems(4);
    sort($to_index);
    if ($fifo) {
      $expected = [$ids[0][0], $ids[0][2], $ids[1][0], $ids[1][1]];
    }
    else {
      $expected = [$ids[0][1], $ids[0][2], $ids[1][0], $ids[1][2]];
    }
    $this->assertEquals($expected, $to_index);

    $to_index = $this->tracker->getRemainingItems(1, $datasource_1);
    if ($fifo) {
      $expected = [$ids[0][0]];
    }
    else {
      $expected = [$ids[0][1]];
    }
    $this->assertEquals($expected, $to_index);

    $to_index = $this->tracker->getRemainingItems(-1);
    sort($to_index);
    $expected = array_merge($ids[0], $ids[1]);
    $this->assertEquals($expected, $to_index);

    $to_index = $this->tracker->getRemainingItems(-1, $datasource_2);
    sort($to_index);
    $this->assertEquals($ids[1], $to_index);

    // Make sure that tracking an unindexed item as updated will not affect its
    // position for FIFO, but will get it to the front for LIFO. (If we do this
    // with the item that's in front for FIFO anyways, we can use the same code
    // in both cases.)
    $this->tracker->trackItemsUpdated([$ids[0][0]]);
    $this->timeService->advanceTime();
    $to_index = $this->tracker->getRemainingItems(1, $datasource_1);
    $this->assertEquals([$ids[0][0]], $to_index);

    // Make sure calling methods with an empty $ids array doesn't blow anything
    // up.
    $this->tracker->trackItemsInserted([]);
    $this->tracker->trackItemsUpdated([]);
    $this->tracker->trackItemsIndexed([]);
    $this->tracker->trackItemsDeleted([]);

    // None of this should have changed the indexing status of any items.
    $this->assertIndexingStatus(0, 6);
    $this->assertIndexingStatus(0, 3, $datasource_1);
    $this->assertIndexingStatus(0, 3, $datasource_2);

    // Now, change the status of some of the items.
    $this->tracker->trackItemsIndexed([$ids[0][0], $ids[0][1], $ids[1][0]]);
    $this->assertIndexingStatus(3, 6);
    $this->assertIndexingStatus(2, 3, $datasource_1);
    $this->assertIndexingStatus(1, 3, $datasource_2);
    $to_index = $this->tracker->getRemainingItems(-1);
    sort($to_index);
    $expected = [$ids[0][2], $ids[1][1], $ids[1][2]];
    $this->assertEquals($expected, $to_index);

    $this->tracker->trackItemsUpdated([$ids[0][0], $ids[0][2]]);
    $this->timeService->advanceTime();
    $this->assertIndexingStatus(2, 6);
    $this->assertIndexingStatus(1, 3, $datasource_1);
    $this->assertIndexingStatus(1, 3, $datasource_2);
    $to_index = $this->tracker->getRemainingItems(-1);
    sort($to_index);
    array_unshift($expected, $ids[0][0]);
    $this->assertEquals($expected, $to_index);

    $this->tracker->trackItemsDeleted([$ids[1][0], $ids[1][2]]);
    $this->assertIndexingStatus(1, 4);
    $this->assertIndexingStatus(1, 3, $datasource_1);
    $this->assertIndexingStatus(0, 1, $datasource_2);
    $to_index = $this->tracker->getRemainingItems(-1);
    sort($to_index);
    // The last element of $expected is $ids[1][2], which we just deleted.
    unset($expected[3]);
    $this->assertEquals($expected, $to_index);

    // Make sure the right items are "at the front" of the queue in each case.
    if ($fifo) {
      // These are the only two (remaining) items that were never indexed, so
      // they still have their original insert time stamp and thus go first.
      $expected = [$ids[0][2], $ids[1][1]];
    }
    else {
      // We just tracked an update for both of these, so they go first.
      $expected = [$ids[0][0], $ids[0][2]];
    }
    $to_index = $this->tracker->getRemainingItems(2);
    sort($to_index);
    $this->assertEquals($expected, $to_index);

    // Some more status changes.
    $this->tracker->trackItemsInserted([$ids[1][2]]);
    $this->timeService->advanceTime();
    $this->assertIndexingStatus(1, 5);
    $this->assertIndexingStatus(1, 3, $datasource_1);
    $this->assertIndexingStatus(0, 2, $datasource_2);

    $this->tracker->trackItemsIndexed(array_merge($ids[0], $ids[1]));
    $this->assertIndexingStatus(5, 5);
    $this->assertIndexingStatus(3, 3, $datasource_1);
    $this->assertIndexingStatus(2, 2, $datasource_2);

    $this->tracker->trackAllItemsUpdated($datasource_1);
    $this->timeService->advanceTime();
    $this->assertIndexingStatus(2, 5);
    $this->assertIndexingStatus(0, 3, $datasource_1);
    $this->assertIndexingStatus(2, 2, $datasource_2);

    $this->tracker->trackItemsIndexed([$ids[0][0]]);
    $this->assertIndexingStatus(3, 5);
    $this->assertIndexingStatus(1, 3, $datasource_1);
    $this->assertIndexingStatus(2, 2, $datasource_2);

    $this->tracker->trackAllItemsUpdated();
    $this->timeService->advanceTime();
    $this->assertIndexingStatus(0, 5);
    $this->assertIndexingStatus(0, 3, $datasource_1);
    $this->assertIndexingStatus(0, 2, $datasource_2);

    $this->tracker->trackAllItemsDeleted($datasource_2);
    $this->assertIndexingStatus(0, 3);
    $this->assertIndexingStatus(0, 3, $datasource_1);
    $this->assertIndexingStatus(0, 0, $datasource_2);

    $this->tracker->trackAllItemsDeleted();
    $this->assertIndexingStatus(0, 0);
    $this->assertIndexingStatus(0, 0, $datasource_1);
    $this->assertIndexingStatus(0, 0, $datasource_2);
  }

  /**
   * Provides test data for testTracking().
   *
   * @return array[]
   *   An array of argument arrays for testTracking().
   *
   * @see \Drupal\Tests\search_api\Kernel\BasicTrackerTest::testTracking()
   */
  public function trackingDataProvider() {
    return [
      'FIFO' => ['fifo'],
      'LIFO' => ['lifo'],
    ];
  }

  /**
   * Tests whether a method properly handles exceptions.
   *
   * @param string $tracker_method
   *   The method to test.
   * @param array $args
   *   (optional) The arguments to pass to the method.
   * @param bool $uses_transaction
   *   (optional) Whether the method is expected to use a transaction (and roll
   *   it back upon encountering an exception).
   *
   * @dataProvider exceptionHandlingDataProvider
   */
  public function testExceptionHandling($tracker_method, array $args = [], $uses_transaction = FALSE) {
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Database\Connection $connection */
    $connection = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();
    foreach (['select', 'insert', 'update', 'delete'] as $method) {
      $connection->method($method)->willThrowException(new \Exception());
    }
    $transaction = $this->getMockBuilder(Transaction::class)
      ->disableOriginalConstructor()
      ->getMock();
    $rolled_back = FALSE;
    $rollback = function () use (&$rolled_back) {
      $rolled_back = TRUE;
    };
    $transaction->method('rollback')->willReturnCallback($rollback);
    $connection->method('startTransaction')->willReturn($transaction);
    $this->tracker->setDatabaseConnection($connection);

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
    $logger = $this->createMock(LoggerInterface::class);
    $log = [];
    $logger->method('log')->willReturnCallback(function () use (&$log) {
      $log[] = func_get_args();
    });
    $this->tracker->setLogger($logger);

    call_user_func_array([$this->tracker, $tracker_method], $args);

    $this->assertCount(1, $log);
    $this->assertStringStartsWith('%type', $log[0][1]);

    if ($uses_transaction) {
      $this->assertEquals(TRUE, $rolled_back);
    }
  }

  /**
   * Provides test data for testExceptionHandling().
   *
   * @return array[]
   *   An array of argument arrays for testExceptionHandling().
   *
   * @see \Drupal\Tests\search_api\Kernel\BasicTrackerTest::testExceptionHandling()
   */
  public function exceptionHandlingDataProvider() {
    return [
      'trackItemsInserted()' => ['trackItemsInserted', [['']], TRUE],
      'trackItemsUpdated()' => ['trackItemsUpdated', [['']], TRUE],
      'trackAllItemsUpdated()' => ['trackAllItemsUpdated', [], TRUE],
      'trackItemsIndexed()' => ['trackItemsIndexed', [['']], TRUE],
      'trackItemsDeleted()' => ['trackItemsDeleted', [], TRUE],
      'trackAllItemsDeleted()' => ['trackAllItemsDeleted', [], TRUE],
      'getRemainingItems()' => ['getRemainingItems'],
      'getTotalItemsCount()' => ['getTotalItemsCount'],
      'getIndexedItemsCount()' => ['getIndexedItemsCount'],
      'getRemainingItemsCount()' => ['getRemainingItemsCount'],
    ];
  }

  /**
   * Asserts that the current tracking status is as expected.
   *
   * @param int $indexed
   *   The expected number of indexed items.
   * @param int $total
   *   The expected total number of items.
   * @param string|null $datasource_id
   *   (optional) The datasource for which to check indexing status, or NULL to
   *   check for the whole index.
   */
  protected function assertIndexingStatus($indexed, $total, $datasource_id = NULL) {
    $datasource = $datasource_id ? " for datasource $datasource_id" : '';
    $actual_indexed = $this->tracker->getIndexedItemsCount($datasource_id);
    $this->assertEquals($indexed, $actual_indexed, "$actual_indexed items indexed$datasource, $indexed expected.");
    $actual_total = $this->tracker->getTotalItemsCount($datasource_id);
    $this->assertEquals($total, $actual_total, "$actual_total items tracked in total$datasource, $total expected.");
    $remaining = $total - $indexed;
    $actual_remaining = $this->tracker->getRemainingItemsCount($datasource_id);
    $this->assertEquals($remaining, $actual_remaining, "$actual_remaining items remaining to be indexed$datasource, $remaining expected.");
  }

}
