<?php

namespace Drupal\Tests\search_api\Kernel\System;

use Drupal\entity_test\Entity\EntityTestMulRevChanged;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api\Utility\Utility;

/**
 * Tests custom data types integration.
 *
 * @group search_api
 */
class CustomDataTypesTest extends KernelTestBase {

  /**
   * The search server used for testing.
   *
   * @var \Drupal\search_api\ServerInterface
   */
  protected $server;

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'field',
    'search_api',
    'search_api_db',
    'search_api_test_db',
    'search_api_test',
    'user',
    'system',
    'entity_test',
    'text',
  ];

  /**
   * An array of test entities.
   *
   * @var \Drupal\entity_test\Entity\EntityTestMulRevChanged[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('system', ['router']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');

    // Do not use a batch for tracking the initial items after creating an
    // index when running the tests via the GUI. Otherwise, it seems Drupal's
    // Batch API gets confused and the test fails.
    if (!Utility::isRunningInCli()) {
      \Drupal::state()->set('search_api_use_tracking_batch', FALSE);
    }

    $this->installConfig(['search_api_test_db']);

    // Create test entities.
    $this->entities[1] = EntityTestMulRevChanged::create([
      'name' => 'foo bar baz föö smile' . json_decode('"\u1F601"'),
      'body' => 'test test case Case casE',
      'type' => 'entity_test_mulrev_changed',
      'keywords' => ['Orange', 'orange', 'örange', 'Orange'],
      'category' => 'item_category',
    ]);
    $this->entities[2] = EntityTestMulRevChanged::create([
      'name' => 'foo bar baz föö smile',
      'body' => 'test test case Case casE',
      'type' => 'entity_test_mulrev_changed',
      'keywords' => ['strawberry', 'llama'],
      'category' => 'item_category',
    ]);
    $this->entities[1]->save();
    $this->entities[2]->save();

    // Create a test server.
    $this->server = Server::create([
      'name' => 'Server test ~',
      'id' => 'test',
      'status' => 1,
      'backend' => 'search_api_test',
    ]);
    $this->server->save();

    // Set the server (determines the supported data types) and remove all
    // non-base fields from the index (since their config isn't installed).
    $this->index = Index::load('database_search_index');
    $this->index->setServer($this->server)
      ->removeField('body')
      ->removeField('keywords')
      ->removeField('category')
      ->removeField('width');
  }

  /**
   * Tests custom data types integration.
   */
  public function testCustomDataTypes() {
    $original_value = $this->entities[1]->get('name')->getValue()[0]['value'];
    $original_type = $this->index->getField('name')->getType();

    $item = $this->index->loadItem('entity:entity_test_mulrev_changed/1:en');
    $item = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createItemFromObject($this->index, $item, 'entity:entity_test_mulrev_changed/1:en');

    $name_field = $item->getField('name');
    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();
    $label = $name_field->getLabel();

    $this->assertEquals($original_value, $processed_value, 'The processed value matches the original value');
    $this->assertEquals($original_type, $processed_type, 'The processed type matches the original type.');
    $this->assertEquals('Name', $label, 'The label is correctly set.');

    // Reset the fields on the item and change to the supported data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields([]);
    $field = $this->index->getField('name')
      ->setType('search_api_test')
      ->setLabel("Test");
    $this->index->addField($field);

    $name_field = $item->getField('name');
    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEquals($original_value, $processed_value, 'The processed value matches the original value');
    $this->assertEquals('search_api_test', $processed_type, 'The processed type matches the new type.');
    $this->assertEquals('Test', $name_field->getLabel(), 'The label is correctly set.');

    // Reset the fields on the item and change to the non-supported data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields([]);
    $field = $this->index->getField('name')
      ->setType('search_api_test_unsupported');
    $this->index->addField($field);
    $name_field = $item->getField('name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEquals($original_value, $processed_value, 'The processed value matches the original value');
    $this->assertEquals('integer', $processed_type, 'The processed type matches the fallback type.');

    // Reset the fields on the item and change to the data altering data type.
    $item->setFieldsExtracted(FALSE);
    $item->setFields([]);
    $field = $this->index->getField('name')
      ->setType('search_api_test_altering');
    $this->index->addField($field);
    $name_field = $item->getField('name');

    $processed_value = $name_field->getValues()[0];
    $processed_type = $name_field->getType();

    $this->assertEquals(strlen($original_value), $processed_value, 'The processed value matches the altered original value');
    $this->assertEquals('search_api_test_altering', $processed_type, 'The processed type matches the defined type.');
  }

}
