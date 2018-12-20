<?php

namespace Drupal\Tests\search_api\Kernel\Processor;

use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the "Type-specific boosting" processor.
 *
 * @group search_api
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\search_api\processor\TypeBoost
 */
class TypeBoostTest extends ProcessorTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp('type_boost');

    // Create an article node type, if not already present.
    if (!NodeType::load('article')) {
      $article_node_type = NodeType::create([
        'type' => 'article',
        'name' => 'Article',
      ]);
      $article_node_type->save();
    }

    // Create a page node type, if not already present.
    if (!NodeType::load('page')) {
      $page_node_type = NodeType::create([
        'type' => 'page',
        'name' => 'Page',
      ]);
      $page_node_type->save();
    }

    // Setup a node index.
    $datasources = \Drupal::getContainer()
      ->get('search_api.plugin_helper')
      ->createDatasourcePlugins($this->index, ['entity:node']);
    $this->index->setDatasources($datasources);
    $this->index->save();
    $this->container
      ->get('search_api.index_task_manager')
      ->addItemsAll($this->index);
    $index_storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('search_api_index');
    $index_storage->resetCache([$this->index->id()]);
    $this->index = $index_storage->load($this->index->id());
  }

  /**
   * Tests that the correct boost is set on items.
   *
   * @covers ::preprocessIndexItems
   */
  public function testEntityBundleBoost() {
    // Enable the processor indexing.
    $processor = $this->index->getProcessor('type_boost');
    $configuration = [
      'boosts' => [
        'entity:node' => [
          'datasource_boost' => '3.0',
          'bundle_boosts' => [
            'article' => '5.0',
          ],
        ],
      ],
    ];
    $processor->setConfiguration($configuration);
    $this->index->setProcessors(['type_boost' => $processor]);
    $this->index->save();

    // Create a node for both node types.
    $nodes = [];
    foreach (['article', 'page'] as $node_type) {
      $node = Node::create([
        'status' => NodeInterface::PUBLISHED,
        'type' => $node_type,
        'title' => $this->randomString(),
      ]);
      $node->save();
      $nodes[$node->id()] = $node->getTypedData();
    }

    // Prepare and generate Search API items.
    $items = [];
    foreach ($nodes as $nid => $node) {
      $items[] = [
        'datasource' => 'entity:node',
        'item' => $node,
        'item_id' => $nid,
      ];
    }
    $items = $this->generateItems($items);

    // Preprocess items.
    $this->index->preprocessIndexItems($items);

    // Check boost value on article node.
    $boost_expected = $configuration['boosts']['entity:node']['bundle_boosts']['article'];
    $boost_actual = sprintf('%.1f', $items['entity:node/1']->getBoost());
    $this->assertEquals($boost_expected, $boost_actual);

    // Check boost value on page node.
    $boost_expected = $configuration['boosts']['entity:node']['datasource_boost'];
    $boost_actual = sprintf('%.1f', $items['entity:node/2']->getBoost());
    $this->assertEquals($boost_expected, $boost_actual);
  }

}
