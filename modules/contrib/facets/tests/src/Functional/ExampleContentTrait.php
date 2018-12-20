<?php

namespace Drupal\Tests\facets\Functional;

use Drupal\search_api\Entity\Index;

/**
 * Contains helpers to create data that can be used by tests.
 */
trait ExampleContentTrait {

  /**
   * The generated test entities, keyed by ID.
   *
   * @var \Drupal\entity_test\Entity\EntityTestMulRevChanged[]
   */
  protected $entities = [];

  /**
   * Sets up the necessary bundles on the test entity type.
   */
  protected function setUpExampleStructure() {
    entity_test_create_bundle('item', NULL, 'entity_test_mulrev_changed');
    entity_test_create_bundle('article', NULL, 'entity_test_mulrev_changed');
  }

  /**
   * Creates several test entities.
   */
  protected function insertExampleContent() {
    $count = \Drupal::entityQuery('entity_test_mulrev_changed')
      ->count()
      ->execute();

    $entity_test_storage = \Drupal::entityTypeManager()
      ->getStorage('entity_test_mulrev_changed');
    $this->entities[1] = $entity_test_storage->create([
      'name' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'item',
      'keywords' => ['orange'],
      'category' => 'item_category',
    ]);
    $this->entities[1]->save();
    $this->entities[2] = $entity_test_storage->create([
      'name' => 'foo test',
      'body' => 'bar test',
      'type' => 'item',
      'keywords' => ['orange', 'apple', 'grape'],
      'category' => 'item_category',
    ]);
    $this->entities[2]->save();
    $this->entities[3] = $entity_test_storage->create([
      'name' => 'bar',
      'body' => 'test foobar',
      'type' => 'item',
    ]);
    $this->entities[3]->save();
    $this->entities[4] = $entity_test_storage->create([
      'name' => 'foo baz',
      'body' => 'test test test',
      'type' => 'article',
      'keywords' => ['apple', 'strawberry', 'grape'],
      'category' => 'article_category',
    ]);
    $this->entities[4]->save();
    $this->entities[5] = $entity_test_storage->create([
      'name' => 'bar baz',
      'body' => 'foo',
      'type' => 'article',
      'keywords' => ['orange', 'strawberry', 'grape', 'banana'],
      'category' => 'article_category',
    ]);
    $this->entities[5]->save();
    $count = \Drupal::entityQuery('entity_test_mulrev_changed')
      ->count()
      ->execute() - $count;
    $this->assertEquals($count, 5, "$count items inserted.");
  }

  /**
   * Indexes all (unindexed) items on the specified index.
   *
   * @param string $index_id
   *   The ID of the index on which items should be indexed.
   *
   * @return int
   *   The number of successfully indexed items.
   */
  protected function indexItems($index_id) {
    /** @var \Drupal\search_api\IndexInterface $index */
    $index = Index::load($index_id);
    return $index->indexItems();
  }

}
