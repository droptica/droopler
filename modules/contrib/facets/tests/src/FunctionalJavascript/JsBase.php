<?php

namespace Drupal\Tests\facets\FunctionalJavascript;

use Drupal\block\Entity\Block;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\search_api\Entity\Index;

/**
 * Tests for the JS that transforms widgets into form elements.
 */
abstract class JsBase extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'views',
    'search_api',
    'facets',
    'facets_search_api_dependency',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create the users used for the tests.
    $admin_user = $this->drupalCreateUser([
      'administer search_api',
      'administer facets',
      'access administration pages',
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    $this->insertExampleContent();
  }

  /**
   * Setup and insert test content.
   */
  protected function insertExampleContent() {
    entity_test_create_bundle('item', NULL, 'entity_test_mulrev_changed');
    entity_test_create_bundle('article', NULL, 'entity_test_mulrev_changed');

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $entity_1 = $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
    ]);
    $entity_1->save();
    $entity_2 = $entity_test_storage->create([
      'name' => 'foo test',
      'body' => 'bar test',
      'type' => 'item',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ]);
    $entity_2->save();
    $entity_3 = $entity_test_storage->create([
      'name' => 'bar',
      'body' => 'test foobar',
      'type' => 'item',
    ]);
    $entity_3->save();
    $entity_4 = $entity_test_storage->create([
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['apple', 'strawberry', 'grape'],
      'category' => 'article_category',
    ]);
    $entity_4->save();
    $entity_5 = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
    ]);
    $entity_5->save();

    $inserted_entities = \Drupal::entityQuery('entity_test_mulrev_changed')
      ->count()
      ->execute();
    $this->assertEquals(5, $inserted_entities, "5 items inserted.");

    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load('database_search_index');
    $indexed_items = $index->indexItems();
    $this->assertEquals(5, $indexed_items, '5 items indexed.');
  }

  /**
   * Create and place a facet block in the first sidebar.
   *
   * @param string $id
   *   Create a block for a facet.
   */
  protected function createBlock($id) {
    $config = \Drupal::configFactory();
    $settings = [
      'plugin' => 'facet_block:' . $id,
      'region' => 'sidebar_first',
      'id' => $id . '_block',
      'theme' => $config->get('system.theme')->get('default'),
      'label' => ucfirst($id) . ' block',
      'visibility' => [],
      'weight' => 0,
    ];

    foreach (['region', 'id', 'theme', 'plugin', 'weight', 'visibility'] as $key) {
      $values[$key] = $settings[$key];
      // Remove extra values that do not belong in the settings array.
      unset($settings[$key]);
    }
    $values['settings'] = $settings;
    $block = Block::create($values);
    $block->save();
  }

  /**
   * Create a facet.
   *
   * @param string $id
   *   The id of the facet.
   * @param string $field
   *   The field name.
   */
  protected function createFacet($id, $field = 'type') {
    $facet_storage = \Drupal::entityTypeManager()->getStorage('facets_facet');
    // Create and save a facet with a checkbox widget.
    $facet_storage->create([
      'id' => $id,
      'name' => strtoupper($id),
      'url_alias' => $id,
      'facet_source_id' => 'search_api:views_page__search_api_test_view__page_1',
      'field_identifier' => $field,
      'empty_behavior' => ['behavior' => 'none'],
      'weight' => 1,
      'widget' => [
        'type' => 'links',
        'config' => [
          'show_numbers' => TRUE,
          'soft_limit' => 0,
        ],
      ],
      'processor_configs' => [
        'url_processor_handler' => [
          'processor_id' => 'url_processor_handler',
          'weights' => ['pre_query' => -10, 'build' => -10],
          'settings' => [],
        ],
      ],
      'query_operator' => 'AND',
    ])->save();
    $this->createBlock($id);
  }

}
