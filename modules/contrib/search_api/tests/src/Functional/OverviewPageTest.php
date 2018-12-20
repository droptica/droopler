<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\ServerInterface;

/**
 * Tests the Search API overview page.
 *
 * @group search_api
 */
class OverviewPageTest extends SearchApiBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'block',
  ];

  /**
   * The path of the overview page.
   *
   * @var string
   */
  protected $overviewPageUrl;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->adminUser);

    $this->overviewPageUrl = 'admin/config/search/search-api';
  }

  /**
   * Tests various scenarios for the overview page.
   *
   * Uses a single method to save time.
   */
  public function testOverviewPage() {
    $this->checkServerAndIndexCreation();
    $this->checkServerAndIndexStatusChanges();
    $this->checkOperations();
    $this->checkOverviewPermissions();
  }

  /**
   * Tests the creation of a server and an index.
   */
  protected function checkServerAndIndexCreation() {
    $server_name = 'WebTest server';
    $index_name = 'WebTest index';
    $actions = [
      [Url::fromRoute('entity.search_api_server.add_form'), 'Add server'],
      [Url::fromRoute('entity.search_api_index.add_form'), 'Add index'],
    ];

    // Enable the "Local actions" block so we can verify which local actions are
    // displayed.
    Block::create([
      'id' => 'classy_local_actions',
      'theme' => 'classy',
      'weight' => -20,
      'plugin' => 'local_actions_block',
      'region' => 'content',
    ])->save();

    // Make sure the overview is empty.
    $this->drupalGet($this->overviewPageUrl);
    $this->assertLocalAction($actions);

    $this->assertSession()->pageTextNotContains($server_name);
    $this->assertSession()->pageTextNotContains($index_name);

    // Test whether a newly created server appears on the overview page.
    $server = $this->getTestServer();

    $this->drupalGet($this->overviewPageUrl);

    $this->assertSession()->pageTextContains($server_name);
    $this->assertSession()->responseContains($server->get('description'));
    $server_class = Html::cleanCssIdentifier($server->getEntityTypeId() . '-' . $server->id());
    $servers = $this->xpath('//tr[contains(@class,"' . $server_class . '") and contains(@class, "search-api-list-enabled")]');
    $this->assertNotEmpty($servers, 'Server is in proper table');

    // Test whether a newly created index appears on the overview page.
    $index = $this->getTestIndex();

    $this->drupalGet($this->overviewPageUrl);

    $this->assertSession()->pageTextContains($index_name);
    $this->assertSession()->responseContains($index->get('description'));
    $index_class = Html::cleanCssIdentifier($index->getEntityTypeId() . '-' . $index->id());
    $fields = $this->xpath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-enabled")]');
    $this->assertNotEmpty($fields, 'Index is in proper table');
    $this->assertSession()->linkNotExists('Execute pending tasks', 'No pending tasks to execute.');

    // Tests that the "Execute pending tasks" local action is correctly
    // displayed when there are pending tasks.
    \Drupal::getContainer()
      ->get('search_api.task_manager')
      ->addTask('deleteItems', $server, $index, ['']);
    // Due to an (apparent) Core bug we need to clear the cache, otherwise the
    // "local actions" block gets displayed from cache (without the link). See
    // #2722237.
    \Drupal::cache('render')->invalidateAll();
    $this->drupalGet($this->overviewPageUrl);
    $this->assertSession()->linkExists('Execute pending tasks', 0);
  }

  /**
   * Tests enable/disable operations for servers and indexes through the UI.
   */
  protected function checkServerAndIndexStatusChanges() {
    $server = $this->getTestServer();
    $this->assertEntityStatusChange($server);

    // Re-create the index for this test.
    $this->getTestIndex()->delete();
    $index = $this->getTestIndex();
    $this->assertEntityStatusChange($index);

    // Disable the server and test that both itself and the index have been
    // disabled.
    $server->setStatus(FALSE)->save();
    $this->drupalGet($this->overviewPageUrl);
    $server_class = Html::cleanCssIdentifier($server->getEntityTypeId() . '-' . $server->id());
    $index_class = Html::cleanCssIdentifier($index->getEntityTypeId() . '-' . $index->id());
    $servers = $this->xpath('//tr[contains(@class,"' . $server_class . '") and contains(@class, "search-api-list-disabled")]');
    $this->assertNotEmpty($servers, 'The server has been disabled.');
    $indexes = $this->xpath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-disabled")]');
    $this->assertNotEmpty($indexes, 'The index has been disabled.');

    // Test that an index can't be enabled if its server is disabled.
    // @todo A non-working "Enable" link should not be displayed at all.
    $this->clickLink('Enable', 1);
    $this->drupalGet($this->overviewPageUrl);
    $indexes = $this->xpath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-disabled")]');
    $this->assertNotEmpty($indexes, 'The index could not be enabled.');

    // Enable the server and try again.
    $server->setStatus(TRUE)->save();
    $this->drupalGet($this->overviewPageUrl);

    // This time the server is enabled so the first 'enable' link belongs to the
    // index.
    $this->clickLink('Enable');
    $this->drupalGet($this->overviewPageUrl);
    $indexes = $this->xpath('//tr[contains(@class,"' . $index_class . '") and contains(@class, "search-api-list-enabled")]');
    $this->assertNotEmpty($indexes, 'The index has been enabled.');

    // Create a new index without a server assigned and test that it can't be
    // enabled. The overview UI is not very consistent at the moment, so test
    // using API functions for now.
    $index2 = Index::create([
      'id' => 'test_index_2',
      'name' => 'WebTest index 2',
      'datasource_settings' => [
        'entity:node' => [],
      ],
    ]);
    $index2->save();
    $this->assertFalse($index2->status(), 'The newly created index without a server is disabled by default.');

    $index2->setStatus(TRUE)->save();
    $this->assertFalse($index2->status(), 'The newly created index without a server cannot be enabled.');
  }

  /**
   * Asserts enable/disable operations for a search server or index.
   *
   * @param \Drupal\search_api\ServerInterface|\Drupal\search_api\IndexInterface $entity
   *   A search server or index.
   */
  protected function assertEntityStatusChange($entity) {
    $this->drupalGet($this->overviewPageUrl);
    $row_class = Html::cleanCssIdentifier($entity->getEntityTypeId() . '-' . $entity->id());
    $rows = $this->xpath('//tr[contains(@class,"' . $row_class . '") and contains(@class, "search-api-list-enabled")]');
    $this->assertNotEmpty($rows, 'The newly created entity is enabled by default.');

    // The first "Disable" link on the page belongs to our server, the second
    // one to our index.
    $this->clickLink('Disable', $entity instanceof ServerInterface ? 0 : 1);

    // Submit the confirmation form and test that the entity has been disabled.
    $this->submitForm([], 'Disable');
    $rows = $this->xpath('//tr[contains(@class,"' . $row_class . '") and contains(@class, "search-api-list-disabled")]');
    $this->assertNotEmpty($rows, 'The entity has been disabled.');

    // Now enable the entity and verify that the operation succeeded.
    $this->clickLink('Enable');
    $this->drupalGet($this->overviewPageUrl);
    $rows = $this->xpath('//tr[contains(@class,"' . $row_class . '") and contains(@class, "search-api-list-enabled")]');
    $this->assertNotEmpty($rows, 'The entity has benn enabled.');
  }

  /**
   * Tests server operations in the overview page.
   */
  protected function checkOperations() {
    $server = $this->getTestServer();

    $this->drupalGet($this->overviewPageUrl);
    $basic_url = $this->urlGenerator->generateFromRoute('entity.search_api_server.canonical', ['search_api_server' => $server->id()]);
    $destination = "?destination=" . $this->urlGenerator->generateFromRoute('search_api.overview');
    $this->assertSession()->responseContains("<a href=\"$basic_url/edit$destination\">Edit</a>");
    $this->assertSession()->responseContains("<a href=\"$basic_url/disable$destination\">Disable</a>");
    $this->assertSession()->responseContains("<a href=\"$basic_url/delete$destination\">Delete</a>");
    $this->assertSession()->responseNotContains("<a href=\"$basic_url/enable$destination\">Enable</a>");

    $server->setStatus(FALSE)->save();
    $this->drupalGet($this->overviewPageUrl);

    // Since \Drupal\Core\Access\CsrfTokenGenerator uses the current session ID,
    // we cannot verify the validity of the token from here.
    $params = $destination ? "$destination&amp;token=" : '?token=';
    $this->assertSession()->responseContains("<a href=\"$basic_url/enable$params");
    $this->assertSession()->responseNotContains("<a href=\"$basic_url/disable$destination\">Disable</a>");
  }

  /**
   * Tests that the overview has the correct permissions set.
   */
  protected function checkOverviewPermissions() {
    $this->drupalGet('admin/config');
    $this->assertSession()->pageTextContains('Search API');

    $this->drupalGet($this->overviewPageUrl);
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->unauthorizedUser);
    $this->drupalGet($this->overviewPageUrl);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Asserts local actions in the page output.
   *
   * @param array $actions
   *   A list of expected action link titles, keyed by the hrefs.
   */
  protected function assertLocalAction(array $actions) {
    $elements = $this->xpath('//a[contains(@class, :class)]', [
      ':class' => 'button-action',
    ]);
    $index = 0;
    foreach ($actions as $action) {
      /** @var \Drupal\Core\Url $url */
      list($url, $title) = $action;
      // SimpleXML gives us the unescaped text, not the actual escaped markup,
      // so use a pattern instead to check the raw content.
      // This behaviour is a bug in libxml, see
      // https://bugs.php.net/bug.php?id=49437.
      $this->assertSession()->responseMatches('@<a [^>]*class="[^"]*button-action[^"]*"[^>]*>' . preg_quote($title, '@') . '</@');
      $this->assertEquals($url->toString(), $elements[$index++]->getAttribute('href'));
    }
  }

}
