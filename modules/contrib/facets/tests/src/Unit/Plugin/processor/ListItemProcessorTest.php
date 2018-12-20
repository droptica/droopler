<?php

namespace Drupal\Tests\facets\Unit\Plugin\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\facets\Entity\Facet;
use Drupal\facets\FacetSource\FacetSourcePluginInterface;
use Drupal\facets\FacetSource\FacetSourcePluginManager;
use Drupal\facets\Plugin\facets\processor\ListItemProcessor;
use Drupal\facets\Result\Result;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Config\ConfigManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Unit test for processor.
 *
 * @group facets
 */
class ListItemProcessorTest extends UnitTestCase {

  /**
   * The processor to be tested.
   *
   * @var \Drupal\facets\processor\BuildProcessorInterface
   */
  protected $processor;

  /**
   * An array containing the results before the processor has ran.
   *
   * @var \Drupal\facets\Result\Result[]
   */
  protected $results;

  /**
   * Creates a new processor object for use in the tests.
   */
  protected function setUp() {
    parent::setUp();

    $facet = new Facet([], 'facets_facet');
    $this->results = [
      new Result($facet, 1, 1, 10),
      new Result($facet, 2, 2, 5),
      new Result($facet, 3, 3, 15),
    ];

    $config_manager = $this->getMockBuilder(ConfigManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_bundle_info = $this->getMockBuilder(EntityTypeBundleInfo::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Create a search api based facet source and make the property definition
    // return null.
    $data_definition = $this->getMock(ComplexDataDefinitionInterface::class);
    $data_definition->expects($this->any())
      ->method('getPropertyDefinition')
      ->willReturn(NULL);
    $facet_source = $this->getMockBuilder(FacetSourcePluginInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $facet_source->expects($this->any())
      ->method('getDataDefinition')
      ->willReturn($data_definition);

    // Add the plugin manager.
    $pluginManager = $this->getMockBuilder(FacetSourcePluginManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $pluginManager->expects($this->any())
      ->method('hasDefinition')
      ->willReturn(TRUE);
    $pluginManager->expects($this->any())
      ->method('createInstance')
      ->willReturn($facet_source);

    $this->processor = new ListItemProcessor([], 'list_item', [], $config_manager, $entity_field_manager, $entity_type_bundle_info);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.facets.facet_source', $pluginManager);
    \Drupal::setContainer($container);
  }

  /**
   * Tests facet build with field.module field.
   */
  public function testBuildConfigurableField() {
    $module_field = $this->getMockBuilder(FieldStorageConfig::class)
      ->disableOriginalConstructor()
      ->getMock();

    // Make sure that when the processor calls loadConfigEntityByName the field
    // we created here is called.
    $config_manager = $this->getMockBuilder(ConfigManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config_manager->expects($this->exactly(2))
      ->method('loadConfigEntityByName')
      ->willReturn($module_field);

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_bundle_info = $this->getMockBuilder(EntityTypeBundleInfo::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor = new ListItemProcessor([], 'list_item', [], $config_manager, $entity_field_manager, $entity_type_bundle_info);

    // Config entity field facet.
    $module_field_facet = new Facet([], 'facets_facet');
    $module_field_facet->setFieldIdentifier('test_facet');
    $module_field_facet->setFacetSourceId('llama_source');
    $module_field_facet->setResults($this->results);
    $module_field_facet->addProcessor([
      'processor_id' => 'list_item',
      'weights' => [],
      'settings' => [],
    ]);

    /* @var \Drupal\facets\Result\Result[] $module_field_facet- */
    $module_field_results = $processor->build($module_field_facet, $this->results);

    $this->assertCount(3, $module_field_results);
    $this->assertEquals('llama', $module_field_results[0]->getDisplayValue());
    $this->assertEquals('badger', $module_field_results[1]->getDisplayValue());
    $this->assertEquals('kitten', $module_field_results[2]->getDisplayValue());
  }

  /**
   * Tests facet build with field.module field.
   */
  public function testBuildBundle() {
    $module_field = $this->getMockBuilder(FieldStorageConfig::class)
      ->disableOriginalConstructor()
      ->getMock();

    $config_manager = $this->getMockBuilder(ConfigManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config_manager->expects($this->exactly(2))
      ->method('loadConfigEntityByName')
      ->willReturn($module_field);

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_type_bundle_info = $this->getMockBuilder(EntityTypeBundleInfo::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor = new ListItemProcessor([], 'list_item', [], $config_manager, $entity_field_manager, $entity_type_bundle_info);

    // Config entity field facet.
    $module_field_facet = new Facet([], 'facets_facet');
    $module_field_facet->setFieldIdentifier('test_facet');
    $module_field_facet->setFacetSourceId('llama_source');
    $module_field_facet->setResults($this->results);
    $module_field_facet->addProcessor([
      'processor_id' => 'list_item',
      'weights' => [],
      'settings' => [],
    ]);
    /* @var \Drupal\facets\Result\Result[] $module_field_facet- */
    $module_field_results = $processor->build($module_field_facet, $this->results);

    $this->assertCount(3, $module_field_results);
    $this->assertEquals('llama', $module_field_results[0]->getDisplayValue());
    $this->assertEquals('badger', $module_field_results[1]->getDisplayValue());
    $this->assertEquals('kitten', $module_field_results[2]->getDisplayValue());
  }

  /**
   * Tests facet build with base props.
   */
  public function testBuildBaseField() {
    $config_manager = $this->getMockBuilder(ConfigManager::class)
      ->disableOriginalConstructor()
      ->getMock();

    $base_field = $this->getMockBuilder(BaseFieldDefinition::class)
      ->disableOriginalConstructor()
      ->getMock();

    $entity_field_manager = $this->getMockBuilder(EntityFieldManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $entity_field_manager->expects($this->any())
      ->method('getFieldDefinitions')
      ->with('node', '')
      ->willReturn([
        'test_facet_baseprop' => $base_field,
      ]);

    $entity_type_bundle_info = $this->getMockBuilder(EntityTypeBundleInfo::class)
      ->disableOriginalConstructor()
      ->getMock();

    $processor = new ListItemProcessor([], 'list_item', [], $config_manager, $entity_field_manager, $entity_type_bundle_info);

    // Base prop facet.
    $base_prop_facet = new Facet([], 'facets_facet');
    $base_prop_facet->setFieldIdentifier('test_facet_baseprop');
    $base_prop_facet->setFacetSourceId('llama_source');
    $base_prop_facet->setResults($this->results);
    $base_prop_facet->addProcessor([
      'processor_id' => 'list_item',
      'weights' => [],
      'settings' => [],
    ]);

    /** @var \Drupal\facets\Result\Result[] $base_prop_results */
    $base_prop_results = $processor->build($base_prop_facet, $this->results);

    $this->assertCount(3, $base_prop_results);
    $this->assertEquals('llama', $base_prop_results[0]->getDisplayValue());
    $this->assertEquals('badger', $base_prop_results[1]->getDisplayValue());
    $this->assertEquals('kitten', $base_prop_results[2]->getDisplayValue());
  }

  /**
   * Tests configuration.
   */
  public function testConfiguration() {
    $config = $this->processor->defaultConfiguration();
    $this->assertEquals([], $config);
  }

  /**
   * Tests testDescription().
   */
  public function testDescription() {
    $this->assertEquals('', $this->processor->getDescription());
  }

  /**
   * Tests isHidden().
   */
  public function testIsHidden() {
    $this->assertEquals(FALSE, $this->processor->isHidden());
  }

  /**
   * Tests isLocked().
   */
  public function testIsLocked() {
    $this->assertEquals(FALSE, $this->processor->isLocked());
  }

}

namespace Drupal\facets\Plugin\facets\processor;

if (!function_exists('options_allowed_values')) {

  /**
   * Overwrite the global function with a version that returns the test values.
   */
  function options_allowed_values() {
    return [
      1 => 'llama',
      2 => 'badger',
      3 => 'kitten',
    ];
  }

}
