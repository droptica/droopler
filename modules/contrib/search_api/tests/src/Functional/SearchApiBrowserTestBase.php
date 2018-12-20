<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;
use Drupal\Tests\BrowserTestBase;

/**
 * Provides the base class for web tests for Search API.
 */
abstract class SearchApiBrowserTestBase extends BrowserTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'node',
    'search_api',
    'search_api_test',
  ];

  /**
   * An admin user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * The number of meta refresh redirects to follow, or NULL if unlimited.
   *
   * @var int|null
   */
  protected $maximumMetaRefreshCount = NULL;

  /**
   * The number of meta refresh redirects followed during ::drupalGet().
   *
   * @var int
   */
  protected $metaRefreshCount = 0;

  /**
   * The permissions of the admin user.
   *
   * @var string[]
   */
  protected $adminUserPermissions = [
    'administer search_api',
    'access administration pages',
  ];

  /**
   * A user without Search API admin permission.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unauthorizedUser;

  /**
   * The anonymous user used for this test.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $anonymousUser;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The ID of the search index used for this test.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the users used for the tests.
    $this->adminUser = $this->drupalCreateUser($this->adminUserPermissions);
    $this->unauthorizedUser = $this->drupalCreateUser(['access administration pages']);
    $this->anonymousUser = $this->drupalCreateUser();

    // Get the URL generator.
    $this->urlGenerator = $this->container->get('url_generator');

    // Create an article node type, if not already present.
    if (!NodeType::load('article')) {
      $this->drupalCreateContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);
    }

    // Create a page node type, if not already present.
    if (!NodeType::load('page')) {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Page',
      ]);
    }

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }
  }

  /**
   * Creates or loads a server.
   *
   * @return \Drupal\search_api\ServerInterface
   *   A search server.
   */
  public function getTestServer() {
    $server = Server::load('webtest_server');
    if (!$server) {
      $server = Server::create([
        'id' => 'webtest_server',
        'name' => 'WebTest server',
        'description' => 'WebTest server' . ' description',
        'backend' => 'search_api_test',
        'backend_config' => [],
      ]);
      $server->save();
    }

    return $server;
  }

  /**
   * Creates or loads an index.
   *
   * @return \Drupal\search_api\IndexInterface
   *   A search index.
   */
  public function getTestIndex() {
    $this->indexId = 'webtest_index';
    $index = Index::load($this->indexId);
    if (!$index) {
      $index = Index::create([
        'id' => $this->indexId,
        'name' => 'WebTest index',
        'description' => 'WebTest index' . ' description',
        'server' => 'webtest_server',
        'datasource_settings' => [
          'entity:node' => [],
        ],
      ]);
      $index->save();
    }

    return $index;
  }

  /**
   * Returns the system path for the test index.
   *
   * @param string|null $tab
   *   (optional) If set, the path suffix for a specific index tab.
   *
   * @return string
   *   A system path.
   */
  protected function getIndexPath($tab = NULL) {
    $path = 'admin/config/search/search-api/index/' . $this->indexId;
    if ($tab) {
      $path .= "/$tab";
    }
    return $path;
  }

  /**
   * Executes all pending Search API tasks.
   */
  protected function executeTasks() {
    $task_manager = \Drupal::getContainer()->get('search_api.task_manager');
    $task_manager->executeAllTasks();
    $this->assertEquals(0, $task_manager->getTasksCount(), 'No more pending tasks.');
  }

}
