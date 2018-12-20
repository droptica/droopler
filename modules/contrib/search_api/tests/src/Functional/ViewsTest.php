<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\block\Entity\Block;
use Drupal\Component\Utility\Html;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Utility\Utility;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Views integration of the Search API.
 *
 * @group search_api
 */
class ViewsTest extends SearchApiBrowserTestBase {

  use ExampleContentTrait;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'block',
    'language',
    'search_api_test_views',
    'views_ui',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add a second language.
    ConfigurableLanguage::createFromLangcode('nl')->save();

    \Drupal::getContainer()
      ->get('search_api.index_task_manager')
      ->addItemsAll(Index::load($this->indexId));
    $this->insertExampleContent();
    $this->indexItems($this->indexId);

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }
  }

  /**
   * Tests a view with exposed filters.
   */
  public function testView() {
    $this->checkResults([], array_keys($this->entities), 'Unfiltered search');

    $this->checkResults(
      ['search_api_fulltext' => 'foobar'],
      [3],
      'Search for a single word'
    );
    $this->checkResults(
      ['search_api_fulltext' => 'foo test'],
      [1, 2, 4],
      'Search for multiple words'
    );
    $query = [
      'search_api_fulltext' => 'foo test',
      'search_api_fulltext_op' => 'or',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'OR search for multiple words');
    $query = [
      'search_api_fulltext' => 'foobar',
      'search_api_fulltext_op' => 'not',
    ];
    $this->checkResults($query, [1, 2, 4, 5], 'Negated search');
    $query = [
      'search_api_fulltext' => 'foo test',
      'search_api_fulltext_op' => 'not',
    ];
    $this->checkResults($query, [], 'Negated search for multiple words');
    $query = [
      'search_api_fulltext' => 'fo',
    ];
    $label = 'Search for short word';
    $this->checkResults($query, [], $label);
    $this->assertSession()->pageTextContains('You must include at least one positive keyword with 3 characters or more');
    $query = [
      'search_api_fulltext' => 'foo to test',
    ];
    $label = 'Fulltext search including short word';
    $this->checkResults($query, [1, 2, 4], $label);
    $this->assertSession()->pageTextNotContains('You must include at least one positive keyword with 3 characters or more');

    $this->checkResults(['id[value]' => 2], [2], 'Search with ID filter');

    $query = [
      'id[min]' => 2,
      'id[max]' => 4,
      'id_op' => 'between',
    ];
    $this->checkResults($query, [2, 3, 4], 'Search with ID "in between" filter');

    $query = [
      'id[min]' => 2,
      'id[max]' => 4,
      'id_op' => 'not between',
    ];
    $this->checkResults($query, [1, 5], 'Search with ID "not in between" filter');

    $query = [
      'id[value]' => 2,
      'id_op' => '>',
    ];
    $this->checkResults($query, [3, 4, 5], 'Search with ID "greater than" filter');
    $query = [
      'id[value]' => 2,
      'id_op' => '!=',
    ];
    $this->checkResults($query, [1, 3, 4, 5], 'Search with ID "not equal" filter');
    $query = [
      'id_op' => 'empty',
    ];
    $this->checkResults($query, [], 'Search with ID "empty" filter');
    $query = [
      'id_op' => 'not empty',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with ID "not empty" filter');

    $yesterday = strtotime('-1DAY');
    $query = [
      'created[value]' => date('Y-m-d', $yesterday),
      'created_op' => '>',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with "Created after" filter');
    $query = [
      'created[value]' => date('Y-m-d', $yesterday),
      'created_op' => '<',
    ];
    $this->checkResults($query, [], 'Search with "Created before" filter');
    $query = [
      'created_op' => 'empty',
    ];
    $this->checkResults($query, [], 'Search with "empty creation date" filter');
    $query = [
      'created_op' => 'not empty',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with "not empty creation date" filter');

    $this->checkResults(['keywords[value]' => 'apple'], [2, 4], 'Search with Keywords filter');
    $query = [
      'keywords[min]' => 'aardvark',
      'keywords[max]' => 'calypso',
      'keywords_op' => 'between',
    ];
    $this->checkResults($query, [2, 4, 5], 'Search with Keywords "in between" filter');

    // For the keywords filters with comparison operators, exclude entity 1
    // since that contains all the uppercase and special characters weirdness.
    $query = [
      'id[value]' => 1,
      'id_op' => '!=',
      'keywords[value]' => 'melon',
      'keywords_op' => '>=',
    ];
    $this->checkResults($query, [2, 4, 5], 'Search with Keywords "greater than or equal" filter');
    $query = [
      'id[value]' => 1,
      'id_op' => '!=',
      'keywords[value]' => 'banana',
      'keywords_op' => '<',
    ];
    $this->checkResults($query, [2, 4], 'Search with Keywords "less than" filter');
    $query = [
      'keywords[value]' => 'orange',
      'keywords_op' => '!=',
    ];
    $this->checkResults($query, [3, 4], 'Search with Keywords "not equal" filter');
    $query = [
      'keywords_op' => 'empty',
    ];
    $label = 'Search with Keywords "empty" filter';
    $this->checkResults($query, [3], $label, 'all/all/all');
    $query = [
      'keywords_op' => 'not empty',
    ];
    $this->checkResults($query, [1, 2, 4, 5], 'Search with Keywords "not empty" filter');

    $query = [
      'name[value]' => 'foo',
    ];
    $this->checkResults($query, [1, 2, 4], 'Search with Name "contains" filter');
    $query = [
      'name[value]' => 'foo',
      'name_op' => '!=',
    ];
    $this->checkResults($query, [3, 5], 'Search with Name "doesn\'t contain" filter');
    $query = [
      'name_op' => 'empty',
    ];
    $this->checkResults($query, [], 'Search with Name "empty" filter');
    $query = [
      'name_op' => 'not empty',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with Name "not empty" filter');

    $query = [
      'language' => ['***LANGUAGE_site_default***'],
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with "Page content language" filter');
    $query = [
      'language' => ['en'],
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with "English" language filter');
    $query = [
      'language' => [Language::LANGCODE_NOT_SPECIFIED],
    ];
    $this->checkResults($query, [], 'Search with "Not specified" language filter');
    $query = [
      'language' => [
        '***LANGUAGE_language_interface***',
        'zxx',
      ],
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with multiple languages filter');

    $query = [
      'search_api_fulltext' => 'foo to test',
      'id[value]' => 2,
      'id_op' => '>',
      'keywords_op' => 'not empty',
    ];
    $this->checkResults($query, [4], 'Search with multiple filters');

    // Test contextual filters. Configured contextual filters are:
    // 1: datasource
    // 2: type (not = true)
    // 3: keywords (break_phrase = true)
    $this->checkResults([], [4, 5], 'Search with arguments', 'entity:entity_test_mulrev_changed/item/grape');

    // "Type" doesn't have "break_phrase" enabled, so the second argument won't
    // have any effect.
    $this->checkResults([], [2, 4, 5], 'Search with arguments', 'all/item+article/strawberry+apple');

    // Check "OR" contextual filters (using commas).
    $this->checkResults([], [4], 'Search with OR arguments', 'all/item,article/strawberry,apple');

    $this->checkResults([], [], 'Search with unknown datasource argument', 'entity:foobar/all/all');

    $query = [
      'id[value]' => 1,
      'id_op' => '!=',
      'keywords[value]' => 'melon',
      'keywords_op' => '>=',
    ];
    $this->checkResults($query, [2, 5], 'Search with arguments and filters', 'entity:entity_test_mulrev_changed/all/orange');

    // Make sure the datasource filter works correctly with multiple selections.
    $index = Index::load($this->indexId);
    $datasource = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createDatasourcePlugin($index, 'entity:user');
    $index->addDatasource($datasource);
    $index->save();

    $query = [
      'datasource' => ['entity:user', 'entity:entity_test_mulrev_changed'],
      'datasource_op' => 'or',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search with multiple datasource filters (OR)');

    $query = [
      'datasource' => ['entity:user', 'entity:entity_test_mulrev_changed'],
      'datasource_op' => 'and',
    ];
    $this->checkResults($query, [], 'Search with multiple datasource filters (AND)');

    $query = [
      'datasource' => ['entity:user'],
      'datasource_op' => 'not',
    ];
    $this->checkResults($query, [1, 2, 3, 4, 5], 'Search for non-user results');

    $query = [
      'datasource' => ['entity:entity_test_mulrev_changed'],
      'datasource_op' => 'not',
    ];
    $this->checkResults($query, [], 'Search for non-test entity results');

    $query = [
      'datasource' => ['entity:user', 'entity:entity_test_mulrev_changed'],
      'datasource_op' => 'not',
    ];
    $this->checkResults($query, [], 'Search for results of no available datasource');

    $this->regressionTests();

    // Make sure there was a display plugin created for this view.
    /** @var \Drupal\search_api\Display\DisplayInterface[] $displays */
    $displays = \Drupal::getContainer()
      ->get('plugin.manager.search_api.display')
      ->getInstances();

    $display_id = 'views_page:search_api_test_view__page_1';
    $this->assertArrayHasKey($display_id, $displays, 'A display plugin was created for the test view page display.');
    $this->assertArrayHasKey('views_block:search_api_test_view__block_1', $displays, 'A display plugin was created for the test view block display.');
    $this->assertArrayHasKey('views_rest:search_api_test_view__rest_export_1', $displays, 'A display plugin was created for the test view block display.');
    $this->assertEquals('/search-api-test', $displays[$display_id]->getPath(), 'Display returns the correct path.');
    $view_url = Url::fromUserInput('/search-api-test')->toString();
    $this->assertEquals($view_url, $displays[$display_id]->getUrl()->toString(), 'Display returns the correct URL.');
    $this->assertNull($displays['views_block:search_api_test_view__block_1']->getPath(), 'Block display returns the correct path.');
    $this->assertEquals('/search-api-rest-test', $displays['views_rest:search_api_test_view__rest_export_1']->getPath(), 'REST display returns the correct path.');

    $this->assertEquals('database_search_index', $displays[$display_id]->getIndex()->id(), 'Display returns the correct search index.');

    $admin_user = $this->drupalCreateUser([
      'administer search_api',
      'access administration pages',
      'administer views',
    ]);
    $this->drupalLogin($admin_user);

    // Delete the page display for the view.
    $this->drupalGet('admin/structure/views/view/search_api_test_view');
    $this->submitForm([], 'Delete Page');
    $this->submitForm([], 'Save');

    drupal_flush_all_caches();

    $displays = \Drupal::getContainer()
      ->get('plugin.manager.search_api.display')
      ->getInstances();
    $this->assertArrayNotHasKey('views_page:search_api_test_view__page_1', $displays, 'No display plugin was created for the test view page display.');
    $this->assertArrayHasKey('views_block:search_api_test_view__block_1', $displays, 'A display plugin was created for the test view block display.');
    $this->assertArrayHasKey('views_rest:search_api_test_view__rest_export_1', $displays, 'A display plugin was created for the test view block display.');
  }

  /**
   * Contains regression tests for previous, fixed bugs.
   */
  protected function regressionTests() {
    $this->regressionTest2869121();
  }

  /**
   * Tests setting the "Fulltext search" filter to "Required".
   *
   * This previously caused problems with form validation and caching.
   *
   * @see https://www.drupal.org/node/2869121
   * @see https://www.drupal.org/node/2873246
   * @see https://www.drupal.org/node/2871030
   */
  protected function regressionTest2869121() {
    // Make sure setting the fulltext filter to "Required" works as expected.
    $view = View::load('search_api_test_view');
    $displays = $view->get('display');
    $displays['default']['display_options']['filters']['search_api_fulltext']['expose']['required'] = TRUE;
    $displays['default']['display_options']['cache']['type'] = 'search_api_time';
    $view->set('display', $displays);
    $view->save();

    $this->checkResults([], [], 'Search without required fulltext keywords');
    $this->assertSession()->responseNotContains('Error message');
    $this->checkResults(
      ['search_api_fulltext' => 'foo test'],
      [1, 2, 4],
      'Search for multiple words'
    );
    $this->assertSession()->responseNotContains('Error message');
    $this->checkResults(
      ['search_api_fulltext' => 'fo'],
      [],
      'Search for short word'
    );
    $this->assertSession()->pageTextContains('You must include at least one positive keyword with 3 characters or more');

    // Make sure this also works with the exposed form in a block, and doesn't
    // throw fatal errors on all pages with the block.
    $view = View::load('search_api_test_view');
    $displays = $view->get('display');
    $displays['page_1']['display_options']['exposed_block'] = TRUE;
    $view->set('display', $displays);
    $view->save();

    Block::create([
      'id' => 'search_api_test_view',
      'theme' => 'classy',
      'weight' => -20,
      'plugin' => 'views_exposed_filter_block:search_api_test_view-page_1',
      'region' => 'content',
    ])->save();

    $this->drupalGet('');
    // We submit the form three times, to make extra sure all Views caches are
    // triggered.
    for ($i = 0; $i < 3; ++$i) {
      // Flush the page-level caches to make sure the Views cache plugin is
      // used (so we could reproduce the bug if it's there).
      \Drupal::getContainer()->get('cache.page')->deleteAll();
      \Drupal::getContainer()->get('cache.dynamic_page_cache')->deleteAll();
      $this->submitForm([], 'Search');
      $this->assertSession()->addressEquals('search-api-test');
      $this->assertSession()->responseNotContains('Error message');
      $this->assertSession()->pageTextNotContains('search results');
      // Make sure the Views cache was used, none of the two page caches.
      $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
      $this->assertSession()
        ->responseHeaderEquals('X-Drupal-Dynamic-Cache', 'MISS');
    }
  }

  /**
   * Checks the Views results for a certain set of parameters.
   *
   * @param array $query
   *   The GET parameters to set for the view.
   * @param int[]|null $expected_results
   *   (optional) The IDs of the expected results; or NULL to skip checking the
   *   results.
   * @param string $label
   *   (optional) A label for this search, to include in assert messages.
   * @param string $arguments
   *   (optional) A string to append to the search path.
   */
  protected function checkResults(array $query, array $expected_results = NULL, $label = 'Search', $arguments = '') {
    $this->drupalGet('search-api-test/' . $arguments, ['query' => $query]);

    if (isset($expected_results)) {
      $count = count($expected_results);
      if ($count) {
        $this->assertSession()->pageTextContains("Displaying $count search results");
      }
      else {
        $this->assertSession()->pageTextNotContains('search results');
      }

      $expected_results = array_combine($expected_results, $expected_results);
      $actual_results = [];
      foreach ($this->entities as $id => $entity) {
        $entity_label = Html::escape($entity->label());
        if (strpos($this->getSession()->getPage()->getContent(), ">$entity_label<") !== FALSE) {
          $actual_results[$id] = $id;
        }
      }
      $this->assertEquals($expected_results, $actual_results, "$label returned correct results.");
    }
  }

  /**
   * Tests results are ordered correctly and react to exposed sorts.
   */
  public function testViewSorts() {
    // Check default ordering, first exposed sort in config is
    // search_api_relevance.
    $this->checkResultsOrder([], [1, 2, 3, 4, 5]);

    // Make sure the exposed sort works.
    $query = [
      'sort_by' => 'search_api_id_desc',
    ];
    $this->checkResultsOrder($query, [5, 4, 3, 2, 1]);
  }

  /**
   * Checks whether Views results are in a certain order in the sorts test view.
   *
   * @param array $query
   *   The GET parameters to set for the view.
   * @param int[] $expected_results
   *   The IDs of the expected results.
   *
   * @see views.view.search_api_test_sorts.yml
   */
  protected function checkResultsOrder(array $query, array $expected_results) {
    $this->drupalGet('search-api-test-sorts', ['query' => $query]);

    $web_assert = $this->assertSession();
    $rows_xpath = '//div[contains(@class, "views-row")]';
    $web_assert->elementsCount('xpath', $rows_xpath, count($expected_results));
    foreach (array_values($expected_results) as $i => $id) {
      $entity_label = Html::escape($this->entities[$id]->label());
      // XPath offsets are 1-based, not 0-based.
      ++$i;
      $web_assert->elementContains('xpath', "($rows_xpath)[$i]", $entity_label);
    }
  }

  /**
   * Tests the Views admin UI and field handlers.
   */
  public function testViewsAdmin() {
    // Add a field from a related entity to the index to test whether it gets
    // displayed correctly.
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($this->indexId);
    $datasource_id = 'entity:entity_test_mulrev_changed';
    $field = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createField($index, 'author', [
        'label' => 'Author name',
        'type' => 'string',
        'datasource_id' => $datasource_id,
        'property_path' => 'user_id:entity:name',
      ]);
    $index->addField($field);
    $field = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createField($index, 'rendered_item', [
        'label' => 'Rendered HTML output',
        'type' => 'text',
        'property_path' => 'rendered_item',
        'configuration' => [
          'roles' => [AccountInterface::ANONYMOUS_ROLE],
          'view_mode' => [],
        ],
      ]);
    $index->addField($field);
    $index->save();

    // Add some Dutch nodes.
    foreach ([1, 2, 3, 4, 5] as $id) {
      $entity = EntityTestMulRevChanged::load($id);
      $entity = $entity->addTranslation('nl', [
        'body' => "dutch node $id",
        'category' => "dutch category $id",
        'keywords' => ["dutch $id A", "dutch $id B"],
      ]);
      $entity->save();
    }
    $this->entities = EntityTestMulRevChanged::loadMultiple();
    $this->indexItems($this->indexId);

    // For viewing the user name and roles of the user associated with test
    // entities, the logged-in user needs to have the permission to administer
    // both users and permissions.
    $permissions = [
      'administer search_api',
      'access administration pages',
      'administer views',
      'administer users',
      'administer permissions',
    ];
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/structure/views/view/search_api_test_view');
    $this->assertSession()->statusCodeEquals(200);

    // Set the user IDs associated with our test entities.
    $users[] = $this->createUser();
    $users[] = $this->createUser();
    $users[] = $this->createUser();
    $this->entities[1]->setOwnerId($users[0]->id())->save();
    $this->entities[2]->setOwnerId($users[0]->id())->save();
    $this->entities[3]->setOwnerId($users[1]->id())->save();
    $this->entities[4]->setOwnerId($users[1]->id())->save();
    $this->entities[5]->setOwnerId($users[2]->id())->save();

    // Switch to "Table" format.
    $this->clickLink('Unformatted list');
    $this->assertSession()->statusCodeEquals(200);
    $edit = [
      'style[type]' => 'table',
    ];
    $this->submitForm($edit, 'Apply');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Apply');
    $this->assertSession()->statusCodeEquals(200);

    // Add the "User ID" relationship.
    $this->clickLink('Add relationships');
    $edit = [
      'name[search_api_datasource_database_search_index_entity_entity_test_mulrev_changed.user_id]' => 'search_api_datasource_database_search_index_entity_entity_test_mulrev_changed.user_id',
    ];
    $this->submitForm($edit, 'Add and configure relationships');
    $this->submitForm([], 'Apply');

    // Add new fields. First check that the listing seems correct.
    $this->clickLink('Add fields');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Test entity - revisions and data table datasource');
    $this->assertSession()->pageTextContains('Authored on');
    $this->assertSession()->pageTextContains('Body (indexed field)');
    $this->assertSession()->pageTextContains('Index Test index');
    $this->assertSession()->pageTextContains('Item ID');
    $this->assertSession()->pageTextContains('Excerpt');
    $this->assertSession()->pageTextContains('The search result excerpted to show found search terms');
    $this->assertSession()->pageTextContains('Relevance');
    $this->assertSession()->pageTextContains('The relevance of this search result with respect to the query');
    $this->assertSession()->pageTextContains('Language code');
    $this->assertSession()->pageTextContains('The user language code.');
    $this->assertSession()->pageTextContains('(No description available)');
    $this->assertSession()->pageTextNotContains('Error: missing help');

    // Then add some fields.
    $fields = [
      'views.counter',
      'search_api_datasource_database_search_index_entity_entity_test_mulrev_changed.id',
      'search_api_index_database_search_index.search_api_datasource',
      'search_api_datasource_database_search_index_entity_entity_test_mulrev_changed.body',
      'search_api_index_database_search_index.category',
      'search_api_index_database_search_index.keywords',
      'search_api_datasource_database_search_index_entity_entity_test_mulrev_changed.user_id',
      'search_api_entity_user.name',
      'search_api_index_database_search_index.author',
      'search_api_entity_user.roles',
      'search_api_index_database_search_index.rendered_item',
    ];
    $edit = [];
    foreach ($fields as $field) {
      $edit["name[$field]"] = $field;
    }
    $this->submitForm($edit, 'Add and configure fields');
    $this->assertSession()->statusCodeEquals(200);

    // @todo For some strange reason, the "roles" field form is not included
    //   automatically in the series of field forms shown to us by Views. Deal
    //   with this graciously (since it's not really our fault, I hope), but it
    //   would be great to have this working normally.
    $get_field_id = function ($key) {
      return Utility::splitPropertyPath($key, TRUE, '.')[1];
    };
    $fields = array_map($get_field_id, $fields);
    $fields = array_combine($fields, $fields);
    for ($i = 0; $i < count($fields); ++$i) {
      $field = $this->submitFieldsForm();
      if (!$field) {
        break;
      }
      unset($fields[$field]);
    }
    foreach ($fields as $field) {
      $this->drupalGet('admin/structure/views/nojs/handler/search_api_test_view/page_1/field/' . $field);
      $this->submitFieldsForm();
    }

    // Add click sorting for all fields where this is possible.
    $this->clickLink('Settings', 0);
    $edit = [
      'style_options[info][search_api_datasource][sortable]' => 1,
      'style_options[info][category][sortable]' => 1,
      'style_options[info][keywords][sortable]' => 1,
    ];
    $this->submitForm($edit, 'Apply');

    // Add a filter for the "Name" field.
    $this->clickLink('Add filter criteria');
    $edit = [
      'name[search_api_index_database_search_index.name]' => 'search_api_index_database_search_index.name',
    ];
    $this->submitForm($edit, 'Add and configure filter criteria');
    $edit = [
      'options[expose_button][checkbox][checkbox]' => 1,
    ];
    $this->submitForm($edit, 'Expose filter');
    $this->submitPluginForm([]);

    // Add a "Search: Fulltext search" filter.
    $this->clickLink('Add filter criteria');
    $edit = [
      'name[search_api_index_database_search_index.search_api_fulltext]' => 'search_api_index_database_search_index.search_api_fulltext',
    ];
    $this->submitForm($edit, 'Add and configure filter criteria');
    $this->assertSession()->pageTextNotContains('No UI parse mode');
    $edit = [
      'options[expose_button][checkbox][checkbox]' => 1,
    ];
    $this->submitForm($edit, 'Expose filter');
    $this->submitPluginForm([]);

    // Save the view.
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Check the results.
    $this->drupalGet('search-api-test');
    $this->assertSession()->statusCodeEquals(200);

    foreach ($this->entities as $id => $entity) {
      $fields = [
        'search_api_datasource',
        'id',
        'body',
        'category',
        'keywords',
        'user_id',
        'user_id:name',
        'user_id:roles',
      ];
      foreach ($fields as $field) {
        $field_entity = $entity;
        while (strpos($field, ':')) {
          list($direct_property, $field) = Utility::splitPropertyPath($field, FALSE);
          if (empty($field_entity->{$direct_property}[0]->entity)) {
            continue 2;
          }
          $field_entity = $field_entity->{$direct_property}[0]->entity;
        }
        // Check that both the English and the Dutch entity are present in the
        // results, with their correct field values.
        $entities = [$field_entity];
        if ($field_entity->hasTranslation('nl')) {
          $entities[] = $field_entity->getTranslation('nl');
        }
        foreach ($entities as $i => $field_entity) {
          if ($field != 'search_api_datasource') {
            $data = \Drupal::getContainer()
              ->get('search_api.fields_helper')
              ->extractFieldValues($field_entity->get($field));
            if (!$data) {
              $data = ['[EMPTY]'];
            }
          }
          else {
            $data = [$datasource_id];
          }
          $row_num = 2 * $id + $i - 1;
          $prefix = "#$row_num [$field] ";
          $text = $prefix . implode("|$prefix", $data);
          $this->assertSession()->pageTextContains($text);
          // Special case for field "author", which duplicates content of
          // "name".
          if ($field === 'name') {
            $text = str_replace('[name]', '[author]', $text);
            $this->assertSession()->pageTextContains($text);
          }
        }
      }
    }

    // Check whether the expected retrieved fields were listed on the page.
    // These are only "keywords" and "rendered_item", since only fields that
    // correspond to an indexed field are included (not when a field is added
    // via the datasource table), and only if "Use entity field rendering" is
    // disabled.
    // @see search_api_test_views_search_api_query_alter()
    $retrieved_fields = [
      'keywords',
      'rendered_item',
    ];
    foreach ($retrieved_fields as $field_id) {
      $this->assertSession()->pageTextContains("'$field_id'");
    }

    // Check that click-sorting works correctly.
    $options = [
      'query' => [
        'order' => 'category',
        'sort' => 'asc',
      ],
    ];
    $this->drupalGet('search-api-test', $options);
    $this->assertSession()->statusCodeEquals(200);
    $ordered_categories = [
      '[EMPTY]',
      'article_category',
      'article_category',
      'dutch category 1',
      'dutch category 2',
      'dutch category 3',
      'dutch category 4',
      'dutch category 5',
      'item_category',
      'item_category',
    ];
    foreach ($ordered_categories as $i => $category) {
      ++$i;
      $this->assertSession()->pageTextContains("#$i [category] $category");
    }
    $options['query']['sort'] = 'desc';
    $this->drupalGet('search-api-test', $options);
    $this->assertSession()->statusCodeEquals(200);
    foreach (array_reverse($ordered_categories) as $i => $category) {
      ++$i;
      $this->assertSession()->pageTextContains("#$i [category] $category");
    }

    // Check the results with an anonymous visitor. All "name" fields should be
    // empty.
    $this->drupalLogout();
    $this->drupalGet('search-api-test');
    $this->assertSession()->statusCodeEquals(200);
    $html = $this->getSession()->getPage()->getContent();
    $this->assertEquals(10, substr_count($html, '[name] [EMPTY]'));

    // Set "Skip access checks" on the "user_id" relationship and check again.
    // The "name" field should now be listed regardless.
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/structure/views/nojs/handler/search_api_test_view/page_1/relationship/user_id');
    $this->submitForm(['options[skip_access]' => 1], 'Apply');
    $this->submitForm([], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogout();
    $this->drupalGet('search-api-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('[name] [EMPTY]');

    // Run regression tests.
    $this->drupalLogin($admin_user);
    $this->adminUiRegressionTests();
  }

  /**
   * Submits the field handler config form currently displayed.
   *
   * @return string|null
   *   The field ID of the field whose form was submitted. Or NULL if the
   *   current page is no field form.
   */
  protected function submitFieldsForm() {
    $url_parts = explode('/', $this->getUrl());
    $field = array_pop($url_parts);
    if (array_pop($url_parts) != 'field') {
      return NULL;
    }

    $edit['options[fallback_options][multi_separator]'] = '|';
    $edit['options[alter][alter_text]'] = TRUE;
    $edit['options[alter][text]'] = "#{{counter}} [$field] {{ $field }}";
    $edit['options[empty]'] = "#{{counter}} [$field] [EMPTY]";

    switch ($field) {
      case 'counter':
        $edit = [
          'options[exclude]' => TRUE,
        ];
        break;

      case 'id':
        $edit['options[field_rendering]'] = FALSE;
        break;

      case 'search_api_datasource':
        unset($edit['options[fallback_options][multi_separator]']);
        break;

      case 'body':
        break;

      case 'category':
        break;

      case 'keywords':
        $edit['options[field_rendering]'] = FALSE;
        break;

      case 'user_id':
        $edit['options[field_rendering]'] = FALSE;
        $edit['options[fallback_options][display_methods][user][display_method]'] = 'id';
        break;

      case 'author':
        break;

      case 'roles':
        $edit['options[field_rendering]'] = FALSE;
        $edit['options[fallback_options][display_methods][user_role][display_method]'] = 'id';
        break;

      case 'rendered_item':
        // "Rendered item" isn't based on a Field API field, so there is no
        // "Fallback options" form (added otherwise by SearchApiEntityField).
        unset($edit['options[fallback_options][multi_separator]']);
        break;
    }

    $this->submitPluginForm($edit);

    return $field;
  }

  /**
   * Submits a Views plugin's configuration form.
   *
   * @param array $edit
   *   The values to set in the form.
   */
  protected function submitPluginForm(array $edit) {
    $button_label = 'Apply';
    $buttons = $this->xpath('//input[starts-with(@value, :label)]', [':label' => $button_label]);
    if ($buttons) {
      $button_label = $buttons[0]->getAttribute('value');
    }

    $this->submitForm($edit, $button_label);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Contains regression tests for previous, fixed bugs in the Views UI.
   */
  protected function adminUiRegressionTests() {
    $this->regressionTest2883807();
  }

  /**
   * Verifies that adding a contextual filter doesn't trigger a notice.
   *
   * @see https://www.drupal.org/node/2883807
   */
  protected function regressionTest2883807() {
    $this->drupalGet('admin/structure/views/nojs/add-handler/search_api_test_view/page_1/argument');
    $edit = [
      'name[search_api_index_database_search_index.author]' => TRUE,
    ];
    $this->submitForm($edit, 'Add and configure contextual filters');
    $this->submitForm([], 'Apply');
    $this->submitForm([], 'Save');
  }

  /**
   * Checks whether highlighting of results works correctly.
   *
   * @see views.view.search_api_test_cache.yml
   */
  public function testHighlighting() {
    // Add the Highlight processor to the search index.
    $index = Index::load('database_search_index');
    $processor = $this->container
      ->get('search_api.plugin_helper')
      ->createProcessorPlugin($index, 'highlight');
    $index->addProcessor($processor);
    $index->save();

    $path = 'search-api-test-search-view-caching-none';
    $this->drupalGet($path);
    $this->assertSession()->responseContains('foo bar baz');

    $options['query']['search_api_fulltext'] = 'foo';
    $this->drupalGet($path, $options);
    $this->assertSession()->responseContains('<strong>foo</strong> bar baz');
  }

  /**
   * {@inheritdoc}
   */
  protected function initConfig(ContainerInterface $container) {
    parent::initConfig($container);

    // This will just set the Drupal state to include the necessary bundles for
    // our test entity type. Otherwise, fields from those bundles won't be found
    // and thus removed from the test index. (We can't do it in setUp(), before
    // calling the parent method, since the container isn't set up at that
    // point.)
    $bundles = [
      'entity_test_mulrev_changed' => ['label' => 'Entity Test Bundle'],
      'item' => ['label' => 'item'],
      'article' => ['label' => 'article'],
    ];
    \Drupal::state()->set('entity_test_mulrev_changed.bundles', $bundles);
  }

}
