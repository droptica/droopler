<?php

namespace Drupal\Tests\search_api\Kernel\Processor;

use Drupal\node\Entity\Node;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Utility\Utility;

/**
 * Tests the "Add URL" processor at a higher level.
 *
 * @group search_api
 *
 * @coversDefaultClass \Drupal\search_api\Plugin\search_api\processor\AddURL
 */
class AddURLKernelTest extends ProcessorTestBase {

  /**
   * The nodes created for testing.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  public function setUp($processor = NULL) {
    parent::setUp('add_url');

    $url_field = new Field($this->index, 'url');
    $url_field->setType('string');
    $url_field->setPropertyPath('search_api_url');
    $url_field->setLabel('Item URL');
    $this->index->addField($url_field);
    $this->index->save();

    $this->nodes[0] = Node::create([
      'title' => 'Test',
      'type' => 'article',
    ]);
    $this->nodes[0]->save();
  }

  /**
   * Tests extracting the field for a search item.
   *
   * @covers ::addFieldValues
   */
  public function testItemFieldExtraction() {
    $node = $this->nodes[0];
    $id = Utility::createCombinedId('entity:node', $node->id() . ':en');
    $item = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createItemFromObject($this->index, $node->getTypedData(), $id);

    // Extract field values and check the value of the URL field.
    $fields = $item->getFields();
    $expected = [$node->toUrl('canonical')->toString()];
    $this->assertEquals($expected, $fields['url']->getValues());
  }

}
