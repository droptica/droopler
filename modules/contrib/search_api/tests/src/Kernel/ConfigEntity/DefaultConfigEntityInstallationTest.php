<?php

namespace Drupal\Tests\search_api\Kernel\ConfigEntity;

use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Entity\Server;
use Drupal\search_api_test\PluginTestTrait;

/**
 * Tests that installing config entities from an extension works correctly.
 *
 * @group search_api
 */
class DefaultConfigEntityInstallationTest extends KernelTestBase {

  use PluginTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'search_api',
    'search_api_test',
    'search_api_test_inconsistent_config',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api');
  }

  /**
   * Tests that installing config entities from an extension works correctly.
   */
  public function testExtensionInstallation() {
    $this->installConfig('search_api_test_inconsistent_config');

    $index = Index::load('inconsistent_search_index');
    $this->assertNotNull($index);
    $values = $index->toArray();

    // Make sure the index was not processed at all upon saving.
    $this->assertTrue($index->status());
    $this->assertArrayNotHasKey('rendered_item', $values['processor_settings']);
    $this->assertArrayHasKey('unknown_property', $values['field_settings']);
    $methods = $this->getCalledMethods('processor');
    $this->assertNotContains('preIndexSave', $methods);
  }

  /**
   * Tests that creating new config entities directly works correctly.
   */
  public function testNormalEntityCreation() {
    $dir = __DIR__ . '/../../../search_api_test_inconsistent_config/config/install/';
    $yaml_file = $dir . 'search_api.server.inconsistent_search_server.yml';
    $values = Yaml::decode(file_get_contents($yaml_file));
    Server::create($values)->save();
    $yaml_file = $dir . 'search_api.index.inconsistent_search_index.yml';
    $values = Yaml::decode(file_get_contents($yaml_file));
    Index::create($values)->save();

    $index = Index::load('inconsistent_search_index');
    $this->assertNotNull($index);
    $values = $index->toArray();

    // Make sure the index was not processed at all upon saving.
    $this->assertFalse($index->status());
    $this->assertArrayHasKey('rendered_item', $values['processor_settings']);
    $this->assertArrayNotHasKey('unknown_property', $values['field_settings']);
    $methods = $this->getCalledMethods('processor');
    $this->assertContains('preIndexSave', $methods);
  }

}
