<?php

namespace Drupal\Tests\search_api\Functional;

use Drupal\Core\Serialization\Yaml;
use Drupal\search_api\Entity\Index;

/**
 * Tests field validation on index creation.
 *
 * @group search_api
 */
class FieldIntegrationTest extends SearchApiBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'search_api_test_db',
  ];

  /**
   * Tests that all fields are added to the index, as expected.
   */
  public function testFields() {
    // Load the index defined in the config.
    $index = Index::load('database_search_index');
    $fields = $index->getFields();

    // Load and parse the same configuration file.
    $yaml_file = __DIR__ . '/../../search_api_test_db/config/install/search_api.index.database_search_index.yml';
    $index_configuration = Yaml::decode(file_get_contents($yaml_file));
    $field_settings = $index_configuration['field_settings'];

    // Check that all the fields defined in the config file made it into the
    // index.
    $this->assertEquals(array_keys($fields), array_keys($field_settings));

    // Make sure that the fields have the same type.
    foreach ($field_settings as $setting) {
      $this->assertArrayHasKey($setting['property_path'], $fields);
      $field = $fields[$setting['property_path']];
      $this->assertEquals($setting['label'], $field->getLabel());
      $this->assertEquals($setting['datasource_id'], $field->getDatasourceId());
      $this->assertEquals($setting['type'], $field->getType());
    }
  }

}
