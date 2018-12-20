<?php

namespace Drupal\Tests\search_api\Kernel\System;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\ConsoleException;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\CommandHelper;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\user\Entity\Role;
use Psr\Log\NullLogger;

/**
 * Tests Search API functionality that gets executed by console utilities.
 *
 * @group search_api
 * @coversDefaultClass \Drupal\search_api\Utility\CommandHelper
 */
class CommandHelperTest extends KernelTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'user',
    'system',
    'entity_test',
  ];

  /**
   * System under test.
   *
   * @var \Drupal\search_api\Utility\CommandHelper
   */
  protected $systemUnderTest;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Disable the use of batches for item tracking to simulate a CLI
    // environment.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    // Create a test server.
    Server::create([
      'name' => 'Pink pony server',
      'id' => 'test_server',
      'status' => TRUE,
      'backend' => 'search_api_test',
    ])->save();

    Role::create([
      'id' => 'anonymous',
      'label' => 'anonymous',
    ])->save();
    user_role_grant_permissions('anonymous', ['view test entity']);

    Index::create([
      'name' => 'Test Index',
      'id' => 'test_index',
      'status' => TRUE,
      'datasource_settings' => [
        'entity:entity_test_mulrev_changed' => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
      'server' => 'test_server',
      'options' => ['index_directly' => FALSE],
    ])->save();
    Index::create([
      'name' => 'Secondary index.',
      'id' => 'second_index',
      'status' => FALSE,
      'datasource_settings' => [
        'entity:entity_test_mulrev_changed' => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
      'server' => 'test_server',
      'options' => ['index_directly' => FALSE],
    ])->save();

    $this->setUpExampleStructure();
    $this->insertExampleContent();
    $this->indexItems('test_index');

    $this->systemUnderTest = new CommandHelper(\Drupal::entityTypeManager(), \Drupal::moduleHandler(), 't');
    $this->systemUnderTest->setLogger(new NullLogger());
  }

  /**
   * Tests the "List indexes" command.
   *
   * @covers ::indexListCommand
   */
  public function testListCommand() {
    $results = $this->systemUnderTest->indexListCommand();
    $this->assertInternalType('array', $results);
    $this->assertCount(2, $results);
    $this->assertArrayHasKey('test_index', $results);
    $this->assertArrayHasKey('second_index', $results);
    $this->assertArrayHasKey('id', $results['test_index']);
    $this->assertArrayHasKey('server', $results['test_index']);
    $this->assertArrayHasKey('status', $results['test_index']);
    $this->assertSame('test_index', $results['test_index']['id']);
    $this->assertSame('test_server', $results['test_index']['server']);
    $this->assertSame('enabled', (string) $results['test_index']['status']);
    $this->assertSame('second_index', $results['second_index']['id']);
    $this->assertSame('test_server', $results['second_index']['server']);
    $this->assertSame('disabled', (string) $results['second_index']['status']);

    $index = Index::load('test_index');
    $index->delete();

    $results = $this->systemUnderTest->indexListCommand();
    $this->assertInternalType('array', $results);
    $this->assertArrayNotHasKey('test_index', $results);
    $this->assertArrayHasKey('second_index', $results);
  }

  /**
   * Tests the "Index status" command.
   *
   * @covers ::indexStatusCommand
   */
  public function testStatusCommand() {
    $results = $this->systemUnderTest->indexStatusCommand();
    $this->assertInternalType('array', $results);
    $this->assertCount(2, $results);
    $this->assertArrayHasKey('test_index', $results);
    $this->assertArrayHasKey('id', $results['test_index']);
    $this->assertArrayHasKey('name', $results['test_index']);
    $this->assertSame('test_index', $results['test_index']['id']);
    $this->assertSame('Test Index', $results['test_index']['name']);
    $this->assertSame('second_index', $results['second_index']['id']);
    $this->assertSame('Secondary index.', $results['second_index']['name']);

    $this->assertSame(5, $results['test_index']['total']);
    $this->assertSame(5, $results['test_index']['indexed']);
    $this->assertSame('100%', $results['test_index']['complete']);
  }

  /**
   * Tests the enable index command.
   *
   * @covers ::enableIndexCommand
   */
  public function testEnableIndexCommand() {
    $index = Index::load('second_index');
    $this->assertFalse($index->status());
    $this->systemUnderTest->enableIndexCommand(['second_index']);
    $index = Index::load('second_index');
    $this->assertTrue($index->status());

    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->enableIndexCommand(['foo']);
  }

  /**
   * Tests the enable index command.
   *
   * @covers ::enableIndexCommand
   */
  public function testEnableIndexWithNoIndexes() {
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = Index::loadMultiple();
    foreach ($indexes as $index) {
      $index->delete();
    }

    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->enableIndexCommand(['second_index']);
  }

  /**
   * Tests the clear index command.
   *
   * @covers ::clearIndexCommand
   */
  public function testClearIndexCommand() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('test_index');
    $this->assertSame(5, $index->getTrackerInstance()->getIndexedItemsCount());
    $this->systemUnderTest->clearIndexCommand(['test_index']);
    $this->assertSame(0, $index->getTrackerInstance()->getIndexedItemsCount());
  }

  /**
   * Tests the disable index command.
   *
   * @covers ::disableIndexCommand
   */
  public function testDisableIndexCommand() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('test_index');
    $this->assertTrue($index->status());
    $this->systemUnderTest->disableIndexCommand(['test_index']);
    $index = Index::load('test_index');
    $this->assertFalse($index->status());

    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->disableIndexCommand(['foo']);
  }

  /**
   * Tests the indexItemsToIndexCommand.
   *
   * @covers ::indexItemsToIndexCommand
   */
  public function testIndexItemsToIndexCommand() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('test_index');
    $this->assertSame(5, $index->getTrackerInstance()->getIndexedItemsCount());
    $index->clear();
    $this->assertSame(0, $index->getTrackerInstance()->getIndexedItemsCount());
    $this->systemUnderTest->indexItemsToIndexCommand(['test_index'], 10, 10);
    $this->runBatch();
    $this->assertSame(5, $index->getTrackerInstance()->getIndexedItemsCount());
  }

  /**
   * Tests resetTrackerCommand.
   *
   * @covers ::resetTrackerCommand
   */
  public function testResetTrackerCommand() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('test_index');
    $this->assertSame(5, $index->getTrackerInstance()->getIndexedItemsCount());
    $this->assertSame(5, $index->getTrackerInstance()->getTotalItemsCount());
    $this->systemUnderTest->resetTrackerCommand(['test_index']);
    $this->assertSame(0, $index->getTrackerInstance()->getIndexedItemsCount());
    $this->assertSame(5, $index->getTrackerInstance()->getTotalItemsCount());
  }

  /**
   * Tests searchIndexCommand.
   *
   * @covers ::searchIndexCommand
   */
  public function testSearchIndexCommand() {
    $results = $this->systemUnderTest->searchIndexCommand('test_index');
    $this->assertNotEmpty($results);
    $this->assertCount(2, $results);
    $results = $this->systemUnderTest->searchIndexCommand('test_index', 'test');
    $this->assertNotEmpty($results);
    $this->assertCount(1, $results);
  }

  /**
   * Tests the server list command.
   *
   * @covers ::serverListCommand
   */
  public function testServerListCommand() {
    $result = $this->systemUnderTest->serverListCommand();
    $this->assertInternalType('array', $result);
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('test_server', $result);
    $this->assertSame('test_server', $result['test_server']['id']);
    $this->assertSame('Pink pony server', $result['test_server']['name']);
    $this->assertSame('enabled', (string) $result['test_server']['status']);

    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::load('test_server');
    $server->setStatus(FALSE);
    $server->save();

    $result = $this->systemUnderTest->serverListCommand();
    $this->assertInternalType('array', $result);
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('test_server', $result);
    $this->assertSame('test_server', $result['test_server']['id']);
    $this->assertSame('Pink pony server', $result['test_server']['name']);
    $this->assertSame('disabled', (string) $result['test_server']['status']);

    $server->delete();
    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->serverListCommand();
  }

  /**
   * Tests the server enable command.
   *
   * @covers ::enableServerCommand
   */
  public function testServerEnableCommand() {
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::load('test_server');
    $server->setStatus(FALSE);
    $server->save();

    $this->systemUnderTest->enableServerCommand('test_server');
    $server = Server::load('test_server');
    $this->assertTrue($server->status());

    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->enableServerCommand('foo');
  }

  /**
   * Tests the server disable command.
   *
   * @covers ::disableServerCommand
   */
  public function testServerDisableCommand() {
    $this->systemUnderTest->disableServerCommand('test_server');
    /** @var \Drupal\search_api\ServerInterface $server */
    $server = Server::load('test_server');
    $this->assertFalse($server->status());

    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->enableServerCommand('foo');
  }

  /**
   * Tests the clear server command.
   *
   * @covers ::clearServerCommand
   */
  public function testClearServerCommand() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('test_index');
    $this->assertSame(5, $index->getTrackerInstance()->getIndexedItemsCount());
    $this->systemUnderTest->clearServerCommand('test_server');
    $this->assertSame(0, $index->getTrackerInstance()->getIndexedItemsCount());
  }

  /**
   * Tests setIndexServerCommand.
   *
   * @covers ::setIndexServerCommand
   */
  public function testSetIndexServerCommand() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('test_index');
    $index->setServer(NULL);
    $index->save();

    $index = Index::load('test_index');
    $this->assertSame(NULL, $index->getServerId());

    $this->systemUnderTest->setIndexServerCommand('test_index', 'test_server');

    $index = Index::load('test_index');
    $this->assertSame('test_server', $index->getServerId());
  }

  /**
   * Tests setIndexServerCommand.
   *
   * @covers ::setIndexServerCommand
   */
  public function testSetIndexServerCommandWithInvalidIndex() {
    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->setIndexServerCommand('foo', 'test_server');
  }

  /**
   * Tests setIndexServerCommand.
   *
   * @covers ::setIndexServerCommand
   */
  public function testSetIndexServerCommandWithInvalidServer() {
    $this->setExpectedException(ConsoleException::class);
    $this->systemUnderTest->setIndexServerCommand('test_index', 'bar');
  }

  /**
   * Runs the currently set batch, if any exists.
   */
  protected function runBatch() {
    $batch = &batch_get();
    if ($batch) {
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }

}
