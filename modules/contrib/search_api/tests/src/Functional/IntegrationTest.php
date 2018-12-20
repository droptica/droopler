<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Plugin\search_api\tracker\Basic;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility\Utility;
use Drupal\search_api_test\Plugin\search_api\tracker\TestTracker;
use Drupal\search_api_test\PluginTestTrait;
use Drupal\Tests\search_api\Kernel\PostRequestIndexingTrait;

/**
 * Tests the overall functionality of the Search API framework and admin UI.
 *
 * @group search_api
 */
class IntegrationTest extends SearchApiBrowserTestBase {

  use PluginTestTrait;
  use PostRequestIndexingTrait;

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser2;

  /**
   * The ID of the backend plugin used for the test server.
   *
   * @var string
   */
  protected $serverBackend = 'search_api_test';

  /**
   * The ID of the search server used for this test.
   *
   * @var string
   */
  protected $serverId;

  /**
   * A storage instance for indexes.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $indexStorage;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'search_api',
    'search_api_test',
    'search_api_test_no_ui',
    'field_ui',
    'link',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->indexStorage = \Drupal::entityTypeManager()->getStorage('search_api_index');

    $permissions = [
      'administer search_api',
      'access administration pages',
      'administer nodes',
      'bypass node access',
      'administer content types',
      'administer node fields',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->adminUser2 = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests various operations via the Search API's admin UI.
   */
  public function testFramework() {
    $this->createServer();
    $this->createServerDuplicate();
    $this->checkServerAvailability();
    $this->createIndex();
    $this->createIndexDuplicate();
    $this->editServer();
    $this->editIndex();
    $this->checkUserIndexCreation();
    $this->checkContentEntityTracking();

    $this->enableAllProcessors();
    $this->checkFieldLabels();

    $this->addFieldsToIndex();
    $this->checkDataTypesTable();
    $this->removeFieldsFromIndex();
    $this->checkReferenceFieldsNonBaseFields();

    $this->configureFilter();
    $this->configureFilterPage();
    $this->checkProcessorChanges();
    $this->changeProcessorFieldBoost();

    $this->setReadOnly();
    $this->disableEnableIndex();
    $this->changeIndexDatasource();
    $this->changeIndexServer();
    $this->checkIndexing();
    $this->checkIndexActions();

    $this->deleteServer();
  }

  /**
   * Tests what happens when an index has an integer as id/label.
   *
   * This needs to be in a separate test because we want to test the content
   * tracking behavior as well as the fields / processors editing and adding
   * without messing with the other index. This test also makes sure that the
   * server also has an integer as id/label.
   */
  public function testIntegerIndex() {
    Server::create([
      'id' => 456,
      'name' => 789,
      'description' => 'WebTest server' . ' description',
      'backend' => $this->serverBackend,
      'backend_config' => [],
    ])->save();

    $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'article']);

    $this->drupalGet('admin/config/search/search-api/add-index');

    $this->indexId = 123;
    $edit = [
      'name' => $this->indexId,
      'id' => $this->indexId,
      'status' => 1,
      'description' => 'test Index:: 123~',
      'server' => 456,
      'datasources[entity:node]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains('Please configure the used datasources.');
    $this->submitForm([], 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains('The index was successfully saved.');
    $this->assertEquals(2, $this->countTrackedItems());

    $this->enableAllProcessors();
    $this->checkFieldLabels();

    $this->addFieldsToIndex();
    $this->addFieldsWithDependenciesToIndex();
    $this->removeFieldsDependencies();
    $this->removeFieldsFromIndex();
    $this->checkUnsavedChanges();

    $this->configureFilter();
    $this->configureFilterPage();
    $this->checkProcessorChanges();
    $this->changeProcessorFieldBoost();

    $this->setReadOnly();
    $this->disableEnableIndex();
    $this->changeIndexDatasource();
    $this->changeIndexServer();
    $this->checkIndexing();
    $this->checkIndexActions();
  }

  /**
   * Tests creating a search server via the UI.
   *
   * @param string $server_id
   *   The ID of the server to create.
   */
  protected function createServer($server_id = '_test_server') {
    $this->serverId = $server_id;
    $server_name = 'Search API &{}<>! Server';
    $server_description = 'A >server< used for testing &.';
    $settings_path = 'admin/config/search/search-api/add-server';

    $this->drupalGet($settings_path);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextNotContains('No UI backend');

    $edit = [
      'name' => '',
      'status' => 1,
      'description' => 'A server used for testing.',
      'backend' => $this->serverBackend,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(new FormattableMarkup('@name field is required.', ['@name' => 'Server name']));

    $edit = [
      'name' => $server_name,
      'status' => 1,
      'description' => $server_description,
      'backend' => $this->serverBackend,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains(new FormattableMarkup('@name field is required.', ['@name' => 'Machine-readable name']));

    $edit += [
      'id' => $this->serverId,
    ];

    $this->configureBackendAndSave($edit);

    $this->assertSession()->pageTextContains('The server was successfully saved.');
    $this->assertSession()->addressEquals('admin/config/search/search-api/server/' . $this->serverId);
    $this->assertHtmlEscaped($server_name);
    $this->assertHtmlEscaped($server_description);

    $this->drupalGet('admin/config/search/search-api');
    $this->assertHtmlEscaped($server_name);
    $this->assertHtmlEscaped($server_description);
  }

  /**
   * Lets derived backend integration tests fill their server create form.
   *
   * @param array $edit
   *   The common server form values so far.
   */
  protected function configureBackendAndSave(array $edit) {
    // Nothing to configure here for the test backend.
    $this->submitForm($edit, 'Save');
  }

  /**
   * Tests creating a search server with an existing machine name.
   */
  protected function createServerDuplicate() {
    $server_add_page = 'admin/config/search/search-api/add-server';
    $this->drupalGet($server_add_page);

    $edit = [
      'name' => $this->serverId,
      'id' => $this->serverId,
      'backend' => $this->serverBackend,
    ];

    // Try to submit an server with a duplicate machine name.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests creating a search index via the UI.
   */
  protected function createIndex() {
    $settings_path = 'admin/config/search/search-api/add-index';
    $this->indexId = 'test_index';
    $index_description = 'An >index< used for &! tęsting.';
    $index_name = 'Search >API< test &!^* index';
    $index_datasource = 'entity:node';

    $this->drupalGet($settings_path);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextNotContains('No UI datasource');
    $this->assertSession()->pageTextNotContains('No UI tracker');

    // Make sure plugin labels are only escaped when necessary.
    $this->assertHtmlEscaped('"Test" tracker');
    $this->assertHtmlEscaped('&quot;String label&quot; test tracker');
    $this->assertHtmlEscaped('"Test" datasource');

    // Make sure datasource and tracker plugin descriptions are displayed.
    $dummy_index = Index::create();
    foreach (['createDatasourcePlugins', 'createTrackerPlugins'] as $method) {
      /** @var \Drupal\search_api\Plugin\IndexPluginInterface[] $plugins */
      $plugins = \Drupal::getContainer()
        ->get('search_api.plugin_helper')
        ->$method($dummy_index);
      foreach ($plugins as $plugin) {
        $description = Utility::escapeHtml($plugin->getDescription());
        $this->assertSession()->responseContains($description);
      }
    }

    // Test form validation (required fields).
    $edit = [
      'status' => 1,
      'description' => $index_description,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Index name field is required.');
    $this->assertSession()->pageTextContains('Machine-readable name field is required.');
    $this->assertSession()->pageTextContains('Data sources field is required.');

    $edit = [
      'name' => $index_name,
      'id' => $this->indexId,
      'status' => 1,
      'description' => $index_description,
      'server' => $this->serverId,
      'datasources[' . $index_datasource . ']' => TRUE,
    ];

    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Please configure the used datasources.');

    $this->submitForm([], 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $this->assertSession()->addressEquals($this->getIndexPath());
    $this->assertHtmlEscaped($index_name);

    $this->drupalGet($this->getIndexPath('edit'));
    $this->assertHtmlEscaped($index_name);

    $index = $this->getIndex(TRUE);

    $this->assertTrue($index, 'Index was correctly created.');
    $this->assertEquals($edit['name'], $index->label(), 'Name correctly inserted.');
    $this->assertEquals($edit['id'], $index->id(), 'Index ID correctly inserted.');
    $this->assertTrue($index->status(), 'Index status correctly inserted.');
    $this->assertEquals($edit['description'], $index->getDescription(), 'Index ID correctly inserted.');
    $this->assertEquals($edit['server'], $index->getServerId(), 'Index server ID correctly inserted.');
    $this->assertEquals($index_datasource, $index->getDatasourceIds()[0], 'Index datasource id correctly inserted.');

    // Test the "Save and add fields" button.
    $index2_id = 'test_index2';
    $edit['id'] = $index2_id;
    unset($edit['server']);
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save and add fields');
    $this->assertSession()->pageTextContains('Please configure the used datasources.');

    $this->submitForm([], 'Save and add fields');
    $this->assertSession()->pageTextContains('The index was successfully saved.');
    $this->indexStorage->resetCache([$index2_id]);
    $index = $this->indexStorage->load($index2_id);
    $this->assertSession()->addressEquals($index->toUrl('add-fields'));

    $this->drupalGet('admin/config/search/search-api');
    $this->assertHtmlEscaped($index_name);
    $this->assertHtmlEscaped($index_description);
  }

  /**
   * Tests creating a search index with an existing machine name.
   */
  protected function createIndexDuplicate() {
    $index_add_page = 'admin/config/search/search-api/add-index';
    $this->drupalGet($index_add_page);

    $edit = [
      'name' => $this->indexId,
      'id' => $this->indexId,
      'server' => $this->serverId,
      'datasources[entity:node]' => TRUE,
    ];

    // Try to submit an index with a duplicate machine name.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Try to submit an index with a duplicate machine name after form
    // rebuilding via datasource submit.
    $this->submitForm($edit, 'datasources_configure');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Try to submit an index with a duplicate machine name after form
    // rebuilding via datasource submit using AJAX.
    $this->submitForm($edit, 'datasources_configure');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

  /**
   * Tests whether editing a server works correctly.
   */
  protected function editServer() {
    $path = 'admin/config/search/search-api/server/' . $this->serverId . '/edit';
    $this->drupalGet($path);

    // Check if it's possible to change the machine name.
    $elements = $this->xpath('//form[@id="search-api-server-edit-form"]/div[contains(@class, "form-item-id")]/input[@disabled]');
    $this->assertEquals(1, count($elements), 'Machine name cannot be changed.');

    $tracked_items_before = $this->countTrackedItems();

    $edit = [
      'name' => 'Test server',
    ];
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->indexStorage->load($this->indexId);
    $remaining = $index->getTrackerInstance()->getRemainingItemsCount();
    $this->assertEquals(0, $remaining, 'Index was not scheduled for re-indexing when saving its server.');

    $this->setReturnValue('backend', 'postUpdate', TRUE);
    $this->drupalGet($path);
    $this->submitForm($edit, 'Save');

    $tracked_items = $this->countTrackedItems();
    $remaining = $index->getTrackerInstance()->getRemainingItemsCount();
    $this->assertEquals($tracked_items, $remaining, 'Backend could trigger re-indexing upon save.');
    $this->assertEquals($tracked_items_before, $tracked_items, 'Items are still tracked after re-indexing was triggered.');
  }

  /**
   * Tests editing a search index via the UI.
   */
  protected function editIndex() {
    $tracked_items = $this->countTrackedItems();
    $edit_path = 'admin/config/search/search-api/index/' . $this->indexId . '/edit';
    $this->drupalGet($edit_path);

    // Check if it's possible to change the machine name.
    $elements = $this->xpath('//form[@id="search-api-index-edit-form"]/div[contains(@class, "form-item-id")]/input[@disabled]');
    $this->assertEquals(1, count($elements), 'Machine name cannot be changed.');

    // Test the AJAX functionality for configuring the tracker.
    $edit = ['tracker' => 'search_api_test'];
    $this->submitForm($edit, 'tracker_configure');
    $edit['tracker_config[foo]'] = 'foobar';
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    // Verify that everything was changed correctly.
    $index = $this->getIndex(TRUE);
    $tracker = $index->getTrackerInstance();
    $this->assertTrue($tracker instanceof TestTracker, get_class($tracker));
    $this->assertTrue($tracker instanceof TestTracker, 'Tracker was successfully switched.');
    $configuration = [
      'foo' => 'foobar',
      'dependencies' => [],
    ];
    $this->assertEquals($configuration, $tracker->getConfiguration(), 'Tracker config was successfully saved.');
    $this->assertEquals($tracked_items, $this->countTrackedItems(), 'Items are still correctly tracked.');

    // Revert back to the default tracker for the rest of the test.
    $this->drupalGet($edit_path);
    $edit = ['tracker' => 'default'];
    $this->submitForm($edit, 'tracker_configure');
    $edit['tracker_config[indexing_order]'] = 'fifo';
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The index was successfully saved.');
    $index = $this->getIndex(TRUE);
    $tracker = $index->getTrackerInstance();
    $this->assertTrue($tracker instanceof Basic, 'Tracker was successfully switched.');
  }

  /**
   * Tests that an entity without bundles can be used as a data source.
   */
  protected function checkUserIndexCreation() {
    $edit = [
      'name' => 'IndexName',
      'id' => 'user_index',
      'datasources[entity:user]' => TRUE,
    ];

    $this->drupalGet('admin/config/search/search-api/add-index');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Please configure the used datasources.');

    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The index was successfully saved.');
    $this->assertSession()->pageTextContains($edit['name']);
  }

  /**
   * Tests the server availability.
   */
  protected function checkServerAvailability() {
    $this->drupalGet('admin/config/search/search-api/server/' . $this->serverId . '/edit');

    $this->drupalGet('admin/config/search/search-api');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Enabled');

    $this->setReturnValue('backend', 'isAvailable', FALSE);
    $this->drupalGet('admin/config/search/search-api');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Unavailable');

    $this->setReturnValue('backend', 'isAvailable', TRUE);
  }

  /**
   * Tests whether the tracking information is properly maintained.
   *
   * Will especially test the bundle option of the content entity datasource.
   */
  protected function checkContentEntityTracking() {
    // Initially there should be no tracked items, because there are no nodes.
    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(0, $tracked_items, 'No items are tracked yet.');

    // Add two articles and two pages (one of them "invisible" to Search API).
    $article1 = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'page']);
    $page2 = Node::create([
      'body' => [
        [
          'value' => $this->randomMachineName(32),
          'format' => filter_default_format(),
        ],
      ],
      'title' => $this->randomMachineName(8),
      'type' => 'page',
      'uid' => \Drupal::currentUser()->id(),
    ]);
    $page2->search_api_skip_tracking = TRUE;
    $page2->save();

    // The 3 new nodes without "search_api_skip_tracking" property set should
    // have been added to the tracking table immediately.
    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(3, $tracked_items, 'Three items are tracked.');

    $this->getCalledMethods('backend');
    $page2->delete();
    $methods = $this->getCalledMethods('backend');
    $this->assertEquals([], $methods, 'Tracking of a delete operation could successfully be prevented.');

    // Test disabling the index.
    $settings_path = $this->getIndexPath('edit');
    $this->drupalGet($settings_path);
    $edit = [
      'status' => FALSE,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => FALSE,
      'datasource_configs[entity:node][bundles][selected][page]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(0, $tracked_items, 'No items are tracked.');

    // Test re-enabling the index.
    $this->drupalGet($settings_path);

    $edit = [
      'status' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => TRUE,
      'datasource_configs[entity:node][bundles][selected][page]' => TRUE,
    ];
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(3, $tracked_items, 'Three items are tracked.');

    // Uncheck "default" and don't select any bundles. This should remove all
    // items from the tracking table.
    $edit = [
      'status' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => FALSE,
      'datasource_configs[entity:node][bundles][selected][page]' => FALSE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(0, $tracked_items, 'No items are tracked.');

    // Leave "default" unchecked and select the "article" bundle. This should
    // re-add the two articles to the tracking table.
    $edit = [
      'status' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => TRUE,
      'datasource_configs[entity:node][bundles][selected][page]' => FALSE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(2, $tracked_items, 'Two items are tracked.');

    // Leave "default" unchecked and select only the "page" bundle. This should
    // result in only the page being present in the tracking table.
    $edit = [
      'status' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => FALSE,
      'datasource_configs[entity:node][bundles][selected][page]' => TRUE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(1, $tracked_items, 'One item is tracked.');

    // Check "default" again and select the "article" bundle. This shouldn't
    // change the tracking table, which should still only contain the page.
    $edit = [
      'status' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 1,
      'datasource_configs[entity:node][bundles][selected][article]' => TRUE,
      'datasource_configs[entity:node][bundles][selected][page]' => FALSE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(1, $tracked_items, 'One item is tracked.');

    // Leave "default" checked but now select only the "page" bundle. This
    // should result in only the articles being tracked.
    $edit = [
      'status' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 1,
      'datasource_configs[entity:node][bundles][selected][article]' => FALSE,
      'datasource_configs[entity:node][bundles][selected][page]' => TRUE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(2, $tracked_items, 'Two items are tracked.');

    // Index items, then check whether updating an article is handled correctly.
    $this->triggerPostRequestIndexing();
    $this->getCalledMethods('backend');
    $article1->save();
    $methods = $this->getCalledMethods('backend');
    $this->assertEquals([], $methods, 'No items were indexed right away (before end of page request).');
    $this->triggerPostRequestIndexing();
    $methods = $this->getCalledMethods('backend');
    $this->assertEquals(['indexItems'], $methods, 'Update successfully tracked.');

    $article1->search_api_skip_tracking = TRUE;
    $article1->save();
    $methods = $this->getCalledMethods('backend');
    $this->assertEquals([], $methods, 'Tracking of entity update successfully prevented.');
    unset($article1->search_api_skip_tracking);

    // Delete an article. That should remove it from the item table.
    $article1->delete();

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(1, $tracked_items, 'One item is tracked.');
  }

  /**
   * Counts the number of tracked items in the test index.
   *
   * @return int
   *   The number of tracked items in the test index.
   */
  protected function countTrackedItems() {
    return $this->getIndex()->getTrackerInstance()->getTotalItemsCount();
  }

  /**
   * Counts the number of unindexed items in the test index.
   *
   * @return int
   *   The number of unindexed items in the test index.
   */
  protected function countRemainingItems() {
    return $this->getIndex()->getTrackerInstance()->getRemainingItemsCount();
  }

  /**
   * Counts the number of items indexed on the server for the test index.
   *
   * @return int
   *   The number of items indexed on the server for the test index.
   */
  protected function countItemsOnServer() {
    $key = 'search_api_test.backend.indexed.' . $this->indexId;
    return count(\Drupal::state()->get($key, []));
  }

  /**
   * Enables all processors.
   */
  public function enableAllProcessors() {
    $this->drupalGet($this->getIndexPath('processors'));

    $edit = [
      'status[content_access]' => 1,
      'status[entity_status]' => 1,
      'status[highlight]' => 1,
      'status[html_filter]' => 1,
      'status[ignorecase]' => 1,
      'status[ignore_character]' => 1,
      'status[stopwords]' => 1,
      'status[tokenizer]' => 1,
      'status[transliteration]' => 1,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The indexing workflow was successfully edited.');
  }

  /**
   * Tests that field labels are always properly escaped.
   */
  protected function checkFieldLabels() {
    $content_type_name = '&%@Content()_=';

    // Add a new content type with funky chars.
    $edit = [
      'name' => $content_type_name,
      'type' => '_content_',
    ];
    $this->drupalGet('admin/structure/types/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Save and manage fields');

    // Add a field to that content type with funky chars.
    $field_name = '^6%{[*>.<"field';
    FieldStorageConfig::create([
      'field_name' => 'field__field_',
      'type' => 'string',
      'entity_type' => 'node',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field__field_',
      'entity_type' => 'node',
      'bundle' => '_content_',
      'label' => $field_name,
    ])->save();

    $url_options['query']['datasource'] = 'entity:node';
    $this->drupalGet($this->getIndexPath('fields/add/nojs'), $url_options);
    $this->assertHtmlEscaped($field_name);
    $this->assertSession()->responseContains('(<code>field__field_</code>)');

    $this->addField('entity:node', 'field__field_', $field_name);

    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertHtmlEscaped($field_name);
    // Also check data type labels/descriptions.
    $this->assertHtmlEscaped('"Test" data type');
    $this->assertSession()->responseContains('Dummy <em>data type</em> implementation');

    $edit = [
      'datasource_configs[entity:node][bundles][default]' => 1,
    ];
    $this->drupalGet($this->getIndexPath('edit'));
    $this->assertHtmlEscaped($content_type_name);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $this->addField(NULL, 'rendered_item', 'Rendered HTML output');
    $this->assertHtmlEscaped($content_type_name);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains(' The field configuration was successfully saved.');

    $this->addField(NULL, 'aggregated_field', 'Aggregated field');
    $this->assertHtmlEscaped($field_name);
    $this->submitForm(['fields[entity:node/field__field_]' => TRUE], 'Save');
    $this->assertSession()->pageTextContains(' The field configuration was successfully saved.');
  }

  /**
   * Tests whether adding fields to the index works correctly.
   */
  protected function addFieldsToIndex() {
    // Make sure that hidden properties are not displayed.
    $url_options['query']['datasource'] = '';
    $this->drupalGet($this->getIndexPath('fields/add/nojs'), $url_options);
    $this->assertSession()->pageTextNotContains('Node access information');

    $fields = [
      'nid' => 'ID',
      'title' => 'Title',
      'body' => 'Body',
      'revision_log' => 'Revision log message',
      'uid:entity:name' => 'Authored by » User » Name',
    ];
    foreach ($fields as $property_path => $label) {
      $this->addField('entity:node', $property_path, $label);
    }

    $this->assertSession()->pageTextNotContains('No UI data type');

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();

    $this->assertTrue(empty($fields['nid']), 'Field changes have not been persisted.');
    $this->drupalGet($this->getIndexPath('fields'));
    $this->submitForm([], 'Save changes');
    $this->assertSession()->pageTextContains('The changes were successfully saved.');

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();

    $this->assertArrayHasKey('nid', $fields, 'nid field is indexed.');

    // Ensure that we aren't offered to index properties of the "Content type"
    // property.
    $path = $this->getIndexPath('fields/add/nojs');
    $url_options = ['query' => ['datasource' => 'entity:node']];
    $this->drupalGet($path, $url_options);
    $this->assertSession()->responseNotContains('property_path=type');

    // The "Content access" processor correctly marked fields as locked.
    $this->assertArrayHasKey('uid', $fields, 'uid field is indexed.');
    $this->assertTrue($fields['uid']->isIndexedLocked(), 'uid field is locked.');
    $this->assertTrue($fields['uid']->isTypeLocked(), 'uid field is type-locked.');
    $this->assertEquals('integer', $fields['uid']->getType(), 'uid field has type integer.');
    $this->assertArrayHasKey('status', $fields, 'status field is indexed.');
    $this->assertTrue($fields['status']->isIndexedLocked(), 'status field is locked.');
    $this->assertTrue($fields['status']->isTypeLocked(), 'status field is type-locked.');
    $this->assertEquals('boolean', $fields['status']->getType(), 'status field has type boolean.');

    // Check that a 'parent_data_type.data_type' Search API field type => data
    // type mapping relationship works.
    $this->assertArrayHasKey('body', $fields, 'body field is indexed.');
    $this->assertEquals('text', $fields['body']->getType(), 'Complex field mapping relationship works.');

    // Test renaming of fields.
    $edit = [
      'fields[title][title]' => 'new_title',
      'fields[title][id]' => 'new_id',
      'fields[title][type]' => 'text',
      'fields[title][boost]' => '21.0',
      'fields[revision_log][type]' => 'search_api_test',
    ];
    $this->drupalGet($this->getIndexPath('fields'));
    $this->submitForm($edit, 'Save changes');
    $this->assertSession()->pageTextContains('The changes were successfully saved.');

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();

    $this->assertArrayHasKey('new_id', $fields, 'title field is indexed.');
    $this->assertEquals($edit['fields[title][title]'], $fields['new_id']->getLabel(), 'title field title is saved.');
    $this->assertEquals($edit['fields[title][id]'], $fields['new_id']->getFieldIdentifier(), 'title field id value is saved.');
    $this->assertEquals($edit['fields[title][type]'], $fields['new_id']->getType(), 'title field type is text.');
    $this->assertEquals($edit['fields[title][boost]'], $fields['new_id']->getBoost(), 'title field boost value is 21.');

    $this->assertArrayHasKey('revision_log', $fields, 'revision_log field is indexed.');
    $this->assertEquals($edit['fields[revision_log][type]'], $fields['revision_log']->getType(), 'revision_log field type is search_api_test.');

    // Reset field values to original.
    $edit = [
      'fields[new_id][title]' => 'Title',
      'fields[new_id][id]' => 'title',
    ];
    $this->drupalGet($this->getIndexPath('fields'));
    $this->submitForm($edit, 'Save changes');
    $this->assertSession()->pageTextContains('The changes were successfully saved.');

    // Make sure that property paths are correctly displayed.
    $this->assertSession()->pageTextContains('uid:entity:name');
  }

  /**
   * Tests if the data types table is available and contains correct values.
   */
  protected function checkDataTypesTable() {
    $this->drupalGet($this->getIndexPath('fields'));
    $rows = $this->xpath('//*[@id="search-api-data-types-table"]/*/table/tbody/tr');
    $this->assertTrue(is_array($rows) && !empty($rows), 'Found a datatype listing.');

    /** @var \Behat\Mink\Element\NodeElement $row */
    foreach ($rows as $row) {
      $columns = $row->findAll('xpath', '/td');
      $label = $columns[0]->getText();
      $icon = basename($columns[2]->find('xpath', '/img')->getAttribute('src'));
      $fallback = $columns[3]->getText();

      // Make sure we display the right icon and fallback column.
      if (strpos($label, 'Unsupported') === 0) {
        $this->assertEquals('error.svg', $icon, 'An error icon is shown for unsupported data types.');
        $this->assertNotEquals($fallback, '', 'The fallback data type label is not empty for unsupported data types.');
      }
      else {
        $this->assertEquals('check.svg', $icon, 'A check icon is shown for supported data types.');
        $this->assertEquals('', $fallback, 'The fallback data type label is empty for supported data types.');
      }
    }
  }

  /**
   * Adds a field for a specific property to the index.
   *
   * @param string|null $datasource_id
   *   The property's datasource's ID, or NULL if it is a datasource-independent
   *   property.
   * @param string $property_path
   *   The property path.
   * @param string|null $label
   *   (optional) If given, the label to check for in the success message.
   */
  protected function addField($datasource_id, $property_path, $label = NULL) {
    $path = $this->getIndexPath('fields/add/nojs');
    $url_options = ['query' => ['datasource' => $datasource_id]];
    list($parent_path) = Utility::splitPropertyPath($property_path);
    if ($parent_path) {
      $url_options['query']['property_path'] = $parent_path;
    }
    if ($this->getUrl() !== $this->buildUrl($path, $url_options)) {
      $this->drupalGet($path, $url_options);
    }

    // Unfortunately it doesn't seem possible to specify the clicked button by
    // anything other than label, so we have to pass it as extra POST data.
    $combined_property_path = Utility::createCombinedId($datasource_id, $property_path);
    $this->assertSession()->responseContains('name="' . $combined_property_path . '"');
    $this->submitForm([], $combined_property_path);
    if ($label) {
      $args['%label'] = $label;
      $this->assertSession()->responseContains(new FormattableMarkup('Field %label was added to the index.', $args));
    }
  }

  /**
   * Tests field dependencies.
   */
  protected function addFieldsWithDependenciesToIndex() {
    // Add a new link field.
    FieldStorageConfig::create([
      'field_name' => 'field_link',
      'type' => 'link',
      'entity_type' => 'node',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Link',
    ])->save();

    // Add a new image field, for both articles and basic pages.
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'type' => 'image',
      'entity_type' => 'node',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Image',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Image',
    ])->save();

    $fields = [
      'field_link' => 'Link',
      'field_image' => 'Image',
    ];
    foreach ($fields as $property_path => $label) {
      $this->addField('entity:node', $property_path, $label);
    }
    $this->drupalGet($this->getIndexPath('fields'));
    $this->submitForm([], 'Save changes');

    // Check that index configuration is updated with dependencies.
    $field_dependencies = (array) \Drupal::config('search_api.index.' . $this->indexId)->get('dependencies.config');
    $this->assertTrue(in_array('field.storage.node.field_link', $field_dependencies), 'The link field has been added as a dependency of the index.');
    $this->assertTrue(in_array('field.storage.node.field_image', $field_dependencies), 'The image field has been added as a dependency of the index.');
  }

  /**
   * Tests whether removing fields on which the index depends works correctly.
   */
  protected function removeFieldsDependencies() {
    // Remove a field and make sure that doing so does not remove the search
    // index.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_link/delete');
    $this->assertSession()->pageTextNotContains('The listed configuration will be deleted.');
    $this->assertSession()->pageTextContains('Search index');

    $this->submitForm([], 'Delete');
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_image/delete');
    $this->submitForm([], 'Delete');

    $this->assertNotNull($this->getIndex(), 'Index was not deleted.');

    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('field_link');
    $this->assertSession()->fieldExists('fields[field_image][id]');
    $this->assertSession()
      ->fieldValueEquals('fields[field_image][id]', 'field_image');

    $field_dependencies = \Drupal::config('search_api.index.' . $this->indexId)->get('dependencies.config');
    $this->assertFalse(in_array('field.storage.node.field_link', (array) $field_dependencies), "The link field has been removed from the index's dependencies.");
    $this->assertTrue(in_array('field.storage.node.field_image', (array) $field_dependencies), "The image field has been removed from the index's dependencies.");
  }

  /**
   * Tests whether removing fields from the index works correctly.
   */
  protected function removeFieldsFromIndex() {
    // Find the "Remove" link for the "body" field.
    $links = $this->xpath('//a[@data-drupal-selector=:id]', [':id' => 'edit-fields-body-remove']);
    $this->assertNotEmpty($links, 'Found "Remove" link for body field');
    $this->assertInternalType('array', $links);
    $url_target = $this->getAbsoluteUrl($links[0]->getAttribute('href'));
    $this->drupalGet($url_target);
    $this->drupalGet($this->getIndexPath('fields'));
    $this->submitForm([], 'Save changes');

    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();
    $this->assertTrue(!isset($fields['body']), 'The body field has been removed from the index.');
  }

  /**
   * Tests whether unsaved fields changes work correctly.
   */
  protected function checkUnsavedChanges() {
    $this->addField('entity:node', 'changed', 'Changed');
    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertSession()->pageTextContains('You have unsaved changes.');

    // Log in a different admin user.
    $this->drupalLogin($this->adminUser2);

    // Construct the message that should be displayed.
    $username = [
      '#theme' => 'username',
      '#account' => $this->adminUser,
    ];
    $args = [
      '@user' => \Drupal::getContainer()->get('renderer')->renderPlain($username),
      ':url' => $this->getIndex()->toUrl('break-lock-form')->toString(),
    ];
    $message = (string) new FormattableMarkup('This index is being edited by user @user, and is therefore locked from editing by others. This lock is @age old. Click here to <a href=":url">break this lock</a>.', $args);
    // Since we can't predict the age that will be shown, just check for
    // everything else.
    $message_parts = explode('@age', $message);

    $this->drupalGet($this->getIndexPath('fields/add/nojs'));
    $this->assertSession()->responseContains($message_parts[0]);
    $this->assertSession()->responseContains($message_parts[1]);
    $this->assertFalse($this->xpath('//input[not(@disabled)]'));
    $this->drupalGet($this->getIndexPath('fields/edit/rendered_item'));
    $this->assertSession()->responseContains($message_parts[0]);
    $this->assertSession()->responseContains($message_parts[1]);
    $this->assertFalse($this->xpath('//input[not(@disabled)]'));
    $this->drupalGet($this->getIndexPath('fields'));
    $this->assertSession()->responseContains($message_parts[0]);
    $this->assertSession()->responseContains($message_parts[1]);
    $this->assertFalse($this->xpath('//input[not(@disabled)]'));
    $match_result = preg_match('#fields/break-lock">([^<>]*?)</a>#', $message, $m);
    $this->assertTrue($match_result);
    $this->clickLink($m[1]);

    $this->assertSession()->responseContains(new FormattableMarkup('By breaking this lock, any unsaved changes made by @user will be lost.', $args));
    $this->submitForm([], 'Break lock');
    $this->assertSession()->pageTextContains('The lock has been broken. You may now edit this search index.');
    // Make sure the field has not been added to the index.
    $index = $this->getIndex(TRUE);
    $fields = $index->getFields();
    $this->assertTrue(!isset($fields['changed']), 'The changed field has not been added to the index.');

    // Find the "Remove" link for the "title" field.
    $links = $this->xpath('//a[@data-drupal-selector=:id]', [':id' => 'edit-fields-title-remove']);
    $this->assertNotEmpty($links, 'Found "Remove" link for title field');
    $this->assertInternalType('array', $links);
    $url_target = $this->getAbsoluteUrl($links[0]->getAttribute('href'));
    $this->drupalGet($url_target);

    $this->assertSession()->pageTextContains('You have unsaved changes.');
    $this->submitForm([], 'Cancel');

    $this->assertArrayHasKey('title', $fields, 'The title field has not been removed from the index.');
  }

  /**
   * Tests if non-base fields of referenced entities can be added.
   */
  protected function checkReferenceFieldsNonBaseFields() {
    // Add a new entity_reference field.
    $field_label = 'reference_field';
    FieldStorageConfig::create([
      'field_name' => 'field__reference_field_',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'allowed_values' => [
          [
            'target_type' => 'node',
          ],
        ],
      ],
    ])->save();
    FieldConfig::create([
      'field_name' => 'field__reference_field_',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => $field_label,
    ])->save();
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field__reference_field_', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->save();

    $node_label = $this->getIndex()->getDatasource('entity:node')->label();
    $field_label = "$field_label » $node_label » $field_label";
    $this->addField('entity:node', 'field__reference_field_:entity:field__reference_field_', $field_label);
    $this->drupalGet($this->getIndexPath('fields'));
    $this->submitForm([], 'Save changes');

    $this->drupalGet('node/2/edit');
    $edit = ['field__reference_field_[0][target_id]' => 'Something (2)'];
    $this->drupalGet('node/2/edit');
    $this->submitForm($edit, 'Save');
    $indexed_values = \Drupal::state()->get("search_api_test.backend.indexed.{$this->indexId}", []);
    $this->assertEquals([2], $indexed_values['entity:node/2:en']['field__reference_field_'], 'Correct value indexed for nested non-base field.');
  }

  /**
   * Tests that configuring a processor works.
   */
  protected function configureFilter() {
    $edit = [
      'status[ignorecase]' => 1,
      'processors[ignorecase][settings][fields][title]' => 'title',
      'processors[ignorecase][settings][fields][field__field_]' => FALSE,
    ];
    $this->drupalGet($this->getIndexPath('processors'));
    $this->submitForm($edit, 'Save');
    $index = $this->getIndex(TRUE);
    try {
      $configuration = $index->getProcessor('ignorecase')->getConfiguration();
      unset($configuration['weights']);
      $expected = [
        'fields' => [
          'title',
        ],
        'all_fields' => FALSE,
      ];
      $this->assertEquals($expected, $configuration, 'Title field enabled for ignore case filter.');
    }
    catch (SearchApiException $e) {
      $this->fail('"Ignore case" processor not enabled.');
    }
    $this->assertSession()
      ->pageTextContains('The indexing workflow was successfully edited.');
  }

  /**
   * Tests that the "no values changed" message on the "Processors" tab works.
   */
  public function configureFilterPage() {
    $this->drupalGet($this->getIndexPath('processors'));
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('No values were changed.');
  }

  /**
   * Tests that changing or a processor doesn't always trigger reindexing.
   */
  protected function checkProcessorChanges() {
    $edit = [
      'status[ignorecase]' => 1,
      'processors[ignorecase][settings][fields][title]' => 'title',
    ];
    // Enable just the ignore case processor, just to have a clean default state
    // before testing.
    $this->drupalGet($this->getIndexPath('processors'));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('No values were changed.');
    $this->assertSession()->pageTextNotContains('All content was scheduled for reindexing so the new settings can take effect.');

    $edit['processors[ignorecase][settings][fields][title]'] = FALSE;
    $this->submitForm($edit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('All content was scheduled for reindexing so the new settings can take effect.');
    $this->assertSession()->responseContains($this->getIndex()->toUrl('canonical')->toString());
  }

  /**
   * Tests that a field added by a processor can be changed.
   *
   * For most fields added by processors, such as the "URL field" processor,
   * only be the "Indexed" checkbox should be locked, not type and boost. This
   * method verifies this.
   */
  protected function changeProcessorFieldBoost() {
    // Add the URL field.
    $this->addField(NULL, 'search_api_url', 'URI');

    // Change the boost of the field.
    $fields_path = $this->getIndexPath('fields');
    $this->drupalGet($fields_path);
    $this->submitForm(['fields[url][boost]' => '8.0'], 'Save changes');
    $this->assertSession()->pageTextContains('The changes were successfully saved.');
    $option_field = $this->assertSession()
      ->optionExists('edit-fields-url-boost', '8.0');
    $this->assertTrue($option_field->hasAttribute('selected'), 'Boost is correctly saved.');

    // Change the type of the field.
    $this->drupalGet($fields_path);
    $this->submitForm(['fields[url][type]' => 'text'], 'Save changes');
    $this->assertSession()->pageTextContains('The changes were successfully saved.');
    $option_field = $this->assertSession()
      ->optionExists('edit-fields-url-type', 'text');
    $this->assertTrue($option_field->hasAttribute('selected'), 'Type is correctly saved.');
  }

  /**
   * Sets an index to "read only" and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index is set to "read only", it
   * keeps tracking but won't index any items.
   */
  protected function setReadOnly() {
    $index = $this->getIndex(TRUE);
    $index->reindex();

    $index_path = $this->getIndexPath();
    $settings_path = $index_path . '/edit';

    // Re-enable tracking of all bundles. After this there should be two
    // unindexed items tracked by the index.
    $edit = [
      'status' => TRUE,
      'read_only' => TRUE,
      'datasource_configs[entity:node][bundles][default]' => 0,
      'datasource_configs[entity:node][bundles][selected][article]' => TRUE,
      'datasource_configs[entity:node][bundles][selected][page]' => TRUE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $index = $this->getIndex(TRUE);
    $remaining_before = $this->countRemainingItems();

    $this->drupalGet($index_path);

    $this->assertSession()->pageTextNotContains('Index now');

    // Also try indexing via the API to make sure it is really not possible.
    $indexed = $this->indexItems();
    $this->assertEquals(0, $indexed, 'No items were indexed after setting the index to "read only".');
    $remaining_after = $this->countRemainingItems();
    $this->assertEquals($remaining_before, $remaining_after, 'No items were indexed after setting the index to "read only".');

    // Disable "read only" and verify indexing now works again.
    $edit = [
      'read_only' => FALSE,
      'datasource_configs[entity:node][bundles][default]' => 1,
      'datasource_configs[entity:node][bundles][selected][article]' => FALSE,
      'datasource_configs[entity:node][bundles][selected][page]' => FALSE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $this->drupalGet($index_path);
    $this->submitForm([], 'Index now');
    $this->checkForMetaRefresh();

    $remaining_after = $index->getTrackerInstance()->getRemainingItemsCount();
    $this->assertEquals(0, $remaining_after, 'Items were indexed after removing the "read only" flag.');
  }

  /**
   * Disables and enables an index and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index is disabled, all its items
   * are removed from both the tracker and the server.
   *
   * When it is enabled again, the items are re-added to the tracker.
   */
  protected function disableEnableIndex() {
    // Disable the index and check that no items are tracked.
    $settings_path = $this->getIndexPath('edit');
    $edit = [
      'status' => FALSE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(0, $tracked_items, 'No items are tracked after disabling the index.');
    $tracked_items = \Drupal::database()->select('search_api_item', 'i')->countQuery()->execute()->fetchField();
    $this->assertEquals(0, $tracked_items, 'No items left in tracking table.');

    // @todo Also try to verify whether the items got deleted from the server.

    // Re-enable the index and check that the items are tracked again.
    $edit = [
      'status' => TRUE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals(2, $tracked_items, 'After enabling the index, 2 items are tracked.');
  }

  /**
   * Changes the index's datasources and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index's datasources are changed, the
   * tracker should remove all items from the datasources it no longer needs to
   * handle and add the new ones.
   */
  protected function changeIndexDatasource() {
    $index = $this->getIndex(TRUE);
    $index->reindex();

    $user_count = \Drupal::entityQuery('user')->count()->execute();
    $node_count = \Drupal::entityQuery('node')->count()->execute();

    // Enable indexing of users.
    $settings_path = $this->getIndexPath('edit');
    $edit = [
      'datasources[entity:user]' => TRUE,
      'datasources[entity:node]' => TRUE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Please configure the used datasources.');
    $this->submitForm([], 'Save');
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals($user_count + $node_count, $tracked_items, 'Correct number of items tracked after enabling the "User" datasource.');

    // Disable indexing of users again.
    $edit = [
      'datasources[entity:user]' => FALSE,
      'datasources[entity:node]' => TRUE,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    $this->executeTasks();

    $tracked_items = $this->countTrackedItems();
    $this->assertEquals($node_count, $tracked_items, 'Correct number of items tracked after disabling the "User" datasource.');
  }

  /**
   * Changes the index's server and checks if it reacts correctly.
   *
   * The expected behavior is that, when an index's server is changed, all of
   * the index's items should be removed from the previous server and marked as
   * "unindexed" in the tracker.
   */
  protected function changeIndexServer() {
    $node_count = \Drupal::entityQuery('node')->count()->execute();
    $this->assertEquals($node_count, $this->countTrackedItems(), 'All nodes are correctly tracked by the index.');

    // Index all remaining items on the index.
    $this->indexItems();

    $remaining_items = $this->countRemainingItems();
    $this->assertEquals(0, $remaining_items, 'All items have been successfully indexed.');

    // Create a second search server.
    $this->createServer('test_server_2');

    // Change the index's server to the new one.
    $settings_path = $this->getIndexPath('edit');
    $edit = [
      'server' => $this->serverId,
    ];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The index was successfully saved.');

    // After saving the new index, we should have called reindex.
    $remaining_items = $this->countRemainingItems();
    $this->assertEquals($node_count, $remaining_items, 'All items still need to be indexed.');
  }

  /**
   * Tests whether indexing via the UI works correctly.
   */
  protected function checkIndexing() {
    $node = $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'article']);
    $this->drupalCreateNode(['type' => 'article']);

    // Skip indexing for one node.
    $key = 'search_api_test.backend.indexItems.skip';
    \Drupal::state()->set($key, ['entity:node/' . $node->id() . ':' . $node->language()->getId()]);

    // Ensure all items need to be indexed.
    $this->getIndex()->reindex();

    $this->drupalPostForm($this->getIndexPath(), [], 'Index now');
    $this->assertSession()->statusCodeEquals(200);
    $this->checkForMetaRefresh();
    $count = \Drupal::entityQuery('node')->count()->execute() - 1;
    $this->assertSession()->pageTextContains("Successfully indexed $count items.");
    $this->assertSession()->pageTextContains('1 item could not be indexed.');
    $this->assertSession()->pageTextNotContains("Couldn't index items.");
    $this->assertSession()->pageTextNotContains('An error occurred');

    $this->drupalPostForm($this->getIndexPath(), [], 'Index now');
    $this->assertSession()->statusCodeEquals(200);
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains("Couldn't index items.");
    $this->assertSession()->pageTextNotContains('An error occurred');

    \Drupal::state()->set($key, []);
    $this->setError('backend', 'indexItems');
    $this->drupalPostForm($this->getIndexPath(), [], 'Index now');
    $this->assertSession()->statusCodeEquals(200);
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains("Couldn't index items.");
    $this->assertSession()->pageTextNotContains('An error occurred');

    $this->setError('backend', 'indexItems', FALSE);
    $this->drupalPostForm($this->getIndexPath(), [], 'Index now');
    $this->assertSession()->statusCodeEquals(200);
    $this->checkForMetaRefresh();
    $this->assertSession()->pageTextContains("Successfully indexed 1 item.");
    $this->assertSession()->pageTextNotContains('could not be indexed.');
    $this->assertSession()->pageTextNotContains("Couldn't index items.");
    $this->assertSession()->pageTextNotContains('An error occurred');
  }

  /**
   * Tests the various actions on the index status form.
   */
  protected function checkIndexActions() {
    $assert_session = $this->assertSession();
    $index = $this->getIndex();
    $tracker = $index->getTrackerInstance();
    $label = $index->label();
    $this->indexItems();

    // Manipulate the tracking information to make it slightly off (so
    // rebuilding the tracker will be necessary).
    $deleted = \Drupal::database()->delete('search_api_item')
      ->condition('index_id', $index->id())
      ->condition('item_id', Utility::createCombinedId('entity:node', '2:en'))
      ->execute();
    $this->assertEquals(1, $deleted);
    $manipulated_items_count = \Drupal::entityQuery('node')->count()->execute() - 1;

    $this->assertEquals($manipulated_items_count, $tracker->getIndexedItemsCount());
    $this->assertEquals($manipulated_items_count, $tracker->getTotalItemsCount());
    $this->assertEquals($manipulated_items_count + 1, $this->countItemsOnServer());

    $this->drupalPostForm($this->getIndexPath('reindex'), [], 'Confirm');
    $assert_session->pageTextContains("The search index $label was successfully reindexed.");
    $this->assertEquals(0, $tracker->getIndexedItemsCount());
    $this->assertEquals($manipulated_items_count, $tracker->getTotalItemsCount());
    $this->assertEquals($manipulated_items_count + 1, $this->countItemsOnServer());
    $this->indexItems();

    $this->drupalPostForm($this->getIndexPath('clear'), [], 'Confirm');
    $assert_session->pageTextContains("All items were successfully deleted from search index $label.");
    $this->assertEquals(0, $tracker->getIndexedItemsCount());
    $this->assertEquals($manipulated_items_count, $tracker->getTotalItemsCount());
    $this->assertEquals(0, $this->countItemsOnServer());
    $this->indexItems();

    $this->drupalPostForm($this->getIndexPath('rebuild-tracker'), [], 'Confirm');
    $assert_session->pageTextContains("The tracking information for search index $label will be rebuilt.");
    $this->assertEquals(0, $tracker->getIndexedItemsCount());
    $this->assertEquals($manipulated_items_count + 1, $tracker->getTotalItemsCount());
    $this->assertEquals($manipulated_items_count, $this->countItemsOnServer());
    $this->indexItems();
  }

  /**
   * Tests deleting a search server via the UI.
   */
  protected function deleteServer() {
    $server = Server::load($this->serverId);

    // Load confirmation form.
    $this->drupalGet('admin/config/search/search-api/server/' . $this->serverId . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains(new FormattableMarkup('Are you sure you want to delete the search server %name?', ['%name' => $server->label()]));
    $this->assertSession()->pageTextContains('Deleting a server will disable all its indexes and their searches.');

    // Confirm deletion.
    $this->submitForm([], 'Delete');
    $this->assertSession()->responseContains(new FormattableMarkup('The search server %name has been deleted.', ['%name' => $server->label()]));
    $this->assertFalse(Server::load($this->serverId), 'Server could not be found anymore.');
    $this->assertSession()->addressEquals('admin/config/search/search-api');

    // Confirm that the index hasn't been deleted.
    $this->indexStorage->resetCache([$this->indexId]);
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = $this->indexStorage->load($this->indexId);
    $this->assertTrue($index, 'The index associated with the server was not deleted.');
    $this->assertFalse($index->status(), 'The index associated with the server was disabled.');
    $this->assertNull($index->getServerId(), 'The index was removed from the server.');
  }

  /**
   * Retrieves test index.
   *
   * @param bool $reset
   *   (optional) If TRUE, reset the entity cache before loading.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The test index.
   */
  protected function getIndex($reset = FALSE) {
    if ($reset) {
      $this->indexStorage->resetCache([$this->indexId]);
    }
    return $this->indexStorage->load($this->indexId);
  }

  /**
   * Indexes all (unindexed) items on the specified index.
   *
   * @return int
   *   The number of successfully indexed items.
   */
  protected function indexItems() {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($this->indexId);
    return $index->indexItems();
  }

  /**
   * Ensures that all occurrences of the string are properly escaped.
   *
   * This makes sure that the string is only mentioned in an escaped version and
   * is never double escaped.
   *
   * @param string $string
   *   The raw string to check for.
   */
  protected function assertHtmlEscaped($string) {
    $this->assertSession()->responseContains(Html::escape($string));
    $this->assertSession()->responseNotContains(Html::escape(Html::escape($string)));
    $this->assertSession()->responseNotContains($string);
  }

}
