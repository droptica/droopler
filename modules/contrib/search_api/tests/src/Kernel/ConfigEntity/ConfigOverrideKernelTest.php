<?php

namespace Drupal\Tests\search_api\Kernel\ConfigEntity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Contains tests for config entities with overrides.
 *
 * @group search_api
 */
class ConfigOverrideKernelTest extends KernelTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'search_api',
    'search_api_test',
    'system',
    'user',
  ];

  /**
   * The test server used for this test.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The test index used for this test.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Set up necessary schemas.
    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('system', ['router']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    // Set up overrides.
    $GLOBALS['config']['search_api.server.test_server'] = [
      'name' => 'Overridden server',
      'backend' => 'search_api_test',
      'backend_config' => [
        'test' => 'foobar',
      ],
    ];
    $GLOBALS['config']['search_api.index.test_index'] = [
      'name' => 'Overridden index',
      'server' => 'test_server',
      'processor_settings' => [
        'search_api_test' => [],
      ],
    ];

    // Create a test server and index.
    $this->server = Server::create([
      'id' => 'test_server',
      'name' => 'Test server',
      'backend' => 'does not exist',
    ]);
    $this->index = Index::create([
      'id' => 'test_index',
      'name' => 'Test index',
      'server' => 'unknown_server',
      'datasource_settings' => [
        'entity:user' => [],
      ],
      'tracker_settings' => [
        'default' => [],
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();

    unset($GLOBALS['config']['search_api.server.test_server']);
    unset($GLOBALS['config']['search_api.index.test_index']);
  }

  /**
   * Checks whether saving an index with overrides works correctly.
   */
  public function testIndexSave() {
    $this->server->save();

    // Even though no processors are set on the index, saving it should trigger
    // the test processor's preIndexSave() method (since we added that processor
    // in the override).
    $this->assertEmpty($this->index->getProcessorsByStage(ProcessorInterface::STAGE_PRE_INDEX_SAVE));
    $this->index->save();
    $this->assertEquals(['preIndexSave'], $this->getCalledMethods('processor'));
    $this->assertEmpty($this->index->getProcessorsByStage(ProcessorInterface::STAGE_PRE_INDEX_SAVE));

    // Verify the override is correctly present when loading the index.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($this->index->id());
    $this->assertEquals('Overridden index', $index->label());
    $server = $index->getServerInstance();
    $this->assertNotEmpty($server);
    $this->assertEquals('Overridden server', $server->label());
    $this->assertTrue($index->status());

    // Verify that overrides are not present when loading the index
    // override-free.
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $index_storage */
    $index_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_index');
    $index = $index_storage->loadOverrideFree($index->id());
    $this->assertEquals('Test index', $index->label());

    // Try to change the index's name (starting from the override-free index)
    // and verify a copy with overrides is used for post-save operations.
    $args = [];
    $this->setMethodOverride('backend', 'updateIndex', function () use (&$args) {
      $args = func_get_args();
    });
    $index->set('name', 'New index name')->save();
    $this->assertCount(2, $args);
    $index = $args[1];
    $this->assertEquals('Overridden index', $index->label());

    // Verify the override is correctly present when loading the index.
    $index = Index::load($this->index->id());
    $this->assertEquals('Overridden index', $index->label());
    $this->assertEquals('Overridden server', $index->getServerInstance()
      ->label());

    // Verify the new name is included when loading the index override-free.
    $index = $index_storage->loadOverrideFree($index->id());
    $this->assertEquals('New index name', $index->label());
  }

  /**
   * Checks whether saving a server with overrides works correctly.
   */
  public function testServerSave() {
    // Verify that in postInsert() the backend overrides are already applied.
    $passed_config = [];
    $passed_name = NULL;
    $override = function (BackendInterface $backend) use (&$passed_config, &$passed_name) {
      $passed_config = $backend->getConfiguration();
      $passed_name = $backend->getServer()->label();
    };
    $this->setMethodOverride('backend', 'postInsert', $override);
    $this->server->save();
    $this->assertEquals(['test' => 'foobar'], $passed_config);
    $this->assertEquals('Overridden server', $passed_name);
    $this->assertEquals('Test server', $this->server->label());
    $this->assertTrue($this->server->status());

    // Save the index.
    $this->index->save();

    // Verify that on load, the overrides are correctly applied.
    $server = Server::load($this->server->id());
    $this->assertEquals('Overridden server', $server->label());
    $this->assertTrue($server->status());
    $this->assertEquals('does not exist', $this->server->getBackendId());

    // Verify that in preUpdate() the backend overrides are already applied.
    $this->setMethodOverride('backend', 'preUpdate', $override);
    $this->server->save();
    $this->assertEquals(['test' => 'foobar'], $passed_config);

    // Verify that overriding "status" prevents the server's indexes from being
    // disabled when attempting to disable the server.
    $GLOBALS['config']['search_api.server.test_server']['status'] = TRUE;
    \Drupal::configFactory()->clearStaticCache();
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $server_storage */
    $server_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_server');
    $server = $server_storage->loadOverrideFree($server->id());
    $server->disable()->save();
    \Drupal::configFactory()->clearStaticCache();
    $index = Index::load($this->index->id());
    $this->assertTrue($index->status());
    $server = Server::load($this->server->id());
    $this->assertTrue($server->status());

    // Verify that overrides are not present when loading the server
    // override-free.
    $server = $server_storage->loadOverrideFree($server->id());
    $this->assertEquals('Test server', $server->label());
  }

}
