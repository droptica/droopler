<?php

namespace Drupal\Tests\facets\Kernel\Entity;

use Drupal\Core\Plugin\PluginBase;
use Drupal\facets\Entity\Facet;
use Drupal\facets\Exception\Exception;
use Drupal\facets\Exception\InvalidProcessorException;
use Drupal\facets\Hierarchy\HierarchyPluginManager;
use Drupal\facets\Plugin\facets\hierarchy\Taxonomy;
use Drupal\facets\Plugin\facets\processor\HideNonNarrowingResultProcessor;
use Drupal\facets\Plugin\facets\widget\LinksWidget;
use Drupal\facets\Processor\ProcessorInterface;
use Drupal\facets\Result\Result;
use Drupal\facets\Widget\WidgetPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class FacetTest.
 *
 * Tests getters and setters for the facet entity.
 *
 * @group facets
 * @coversDefaultClass \Drupal\facets\Entity\Facet
 */
class FacetTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('facets_facet');
  }

  /**
   * Tests for getters that don't have setters.
   *
   * @covers ::getDescription
   * @covers ::getName
   */
  public function testDescription() {
    $entity = new Facet(['description' => 'Owls'], 'facets_facet');
    $this->assertEquals('Owls', $entity->getDescription());

    $entity = new Facet(['description' => 'Owls', 'name' => 'owl'], 'facets_facet');
    $this->assertEquals('owl', $entity->getName());
  }

  /**
   * Tests widget behavior.
   *
   * @covers ::setWidget
   * @covers ::getWidget
   * @covers ::getWidgetManager
   * @covers ::getWidgetInstance
   */
  public function testWidget() {
    $entity = new Facet([], 'facets_facet');
    $entity->setWidget('links');

    $manager = $entity->getWidgetManager();
    $this->assertInstanceOf(WidgetPluginManager::class, $manager);

    $config = [
      'soft_limit' => 0,
      'show_numbers' => FALSE,
      'soft_limit_settings' => [
        'show_less_label' => 'Show less',
        'show_more_label' => 'Show more',
      ],
    ];
    $this->assertEquals(['type' => 'links', 'config' => $config], $entity->getWidget());
    $this->assertInstanceOf(LinksWidget::class, $entity->getWidgetInstance());
    $this->assertFalse($entity->getWidgetInstance()->getConfiguration()['show_numbers']);

    $config['show_numbers'] = TRUE;
    $entity->setWidget('links', $config);
    $this->assertEquals(['type' => 'links', 'config' => $config], $entity->getWidget());
    $this->assertInstanceOf(LinksWidget::class, $entity->getWidgetInstance());
    $this->assertTrue($entity->getWidgetInstance()->getConfiguration()['show_numbers']);
  }

  /**
   * Tests an empty widget.
   *
   * @covers ::getWidget
   * @covers ::getWidgetInstance
   */
  public function testEmptyWidget() {
    $entity = new Facet([], 'facets_facet');
    $this->assertNull($entity->getWidget());

    $this->setExpectedException(InvalidProcessorException::class);
    $entity->getWidgetInstance();
  }

  /**
   * Tests widget processor behavior.
   *
   * @covers ::getProcessorsByStage
   * @covers ::getProcessors
   * @covers ::getProcessorConfigs
   * @covers ::addProcessor
   * @covers ::removeProcessor
   * @covers ::loadProcessors
   */
  public function testProcessor() {
    $entity = new Facet([], 'facets_facet');

    $this->assertEmpty($entity->getProcessorConfigs());
    $this->assertEmpty($entity->getProcessors());
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_PRE_QUERY));
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_POST_QUERY));
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_BUILD));
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_SORT));

    $id = 'hide_non_narrowing_result_processor';
    $config = [
      'processor_id' => $id,
      'weights' => [],
      'settings' => [],
    ];
    $entity->addProcessor($config);
    $this->assertEquals([$id => $config], $entity->getProcessorConfigs());

    $this->assertNotEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_BUILD));
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_SORT));
    $processors = $entity->getProcessors();
    $this->assertArrayHasKey('hide_non_narrowing_result_processor', $processors);
    $this->assertInstanceOf(HideNonNarrowingResultProcessor::class, $processors['hide_non_narrowing_result_processor']);

    $entity->removeProcessor($id);
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_BUILD));
    $this->assertEmpty($entity->getProcessorsByStage(ProcessorInterface::STAGE_SORT));
  }

  /**
   * Query type with no defined facet source.
   *
   * @covers ::getQueryType
   */
  public function testGetQueryTypeWithNoFacetSource() {
    $entity = new Facet([], 'facets_facet');

    $this->setExpectedException(Exception::class, 'No facet source defined for facet.');
    $entity->getQueryType();
  }

  /**
   * Tests query operator.
   *
   * @covers ::setQueryOperator
   * @covers ::getQueryOperator
   */
  public function testQueryOperator() {
    $entity = new Facet([], 'facets_facet');

    $this->assertEquals('or', $entity->getQueryOperator());
    $entity->setQueryOperator('and');
    $this->assertEquals('and', $entity->getQueryOperator());
  }

  /**
   * Tests exclude operator.
   *
   * @covers ::getExclude
   * @covers ::setExclude
   */
  public function testExclude() {
    $entity = new Facet([], 'facets_facet');
    $this->assertFalse($entity->getExclude());
    $entity->setExclude(TRUE);
    $this->assertTrue($entity->getExclude());
  }

  /**
   * Tests facet weight.
   *
   * @covers ::setWeight
   * @covers ::getWeight
   */
  public function testWeight() {
    $entity = new Facet([], 'facets_facet');
    $this->assertNull($entity->getWeight());
    $entity->setWeight(12);
    $this->assertEquals(12, $entity->getWeight());

  }

  /**
   * Tests facet visibility.
   *
   * @covers ::setOnlyVisibleWhenFacetSourceIsVisible
   * @covers ::getOnlyVisibleWhenFacetSourceIsVisible
   */
  public function testOnlyVisible() {
    $entity = new Facet([], 'facets_facet');
    $this->assertNull($entity->getOnlyVisibleWhenFacetSourceIsVisible());
    $entity->setOnlyVisibleWhenFacetSourceIsVisible(TRUE);
    $this->assertTrue($entity->getOnlyVisibleWhenFacetSourceIsVisible());

  }

  /**
   * Tests facet only one result.
   *
   * @covers ::getShowOnlyOneResult
   * @covers ::setShowOnlyOneResult
   */
  public function testOnlyOneResult() {
    $entity = new Facet([], 'facets_facet');
    $this->assertFalse($entity->getShowOnlyOneResult());
    $entity->setShowOnlyOneResult(TRUE);
    $this->assertTrue($entity->getShowOnlyOneResult());
  }

  /**
   * Tests url alias.
   *
   * @covers ::getUrlAlias
   * @covers ::setUrlAlias
   */
  public function testUrlAlias() {
    $entity = new Facet([], 'facets_facet');
    $this->assertNull($entity->getUrlAlias());

    $entity->setUrlAlias('owl');
    $this->assertEquals('owl', $entity->getUrlAlias());

    $entity = new Facet(['url_alias' => 'llama'], 'facets_facet');
    $this->assertEquals('llama', $entity->getUrlAlias());
  }

  /**
   * Tests results behavior.
   *
   * @covers ::setResults
   * @covers ::getResults
   * @covers ::isActiveValue
   * @covers ::getActiveItems
   * @covers ::setActiveItems
   * @covers ::setActiveItem
   * @covers ::isActiveValue
   */
  public function testResults() {
    $entity = new Facet([], 'facets_facet');
    /** @var \Drupal\facets\Result\ResultInterface[] $results */
    $results = [
      new Result($entity, 'llama', 'llama', 10),
      new Result($entity, 'badger', 'badger', 15),
      new Result($entity, 'owl', 'owl', 5),
    ];

    $this->assertEmpty($entity->getResults());

    $entity->setResults($results);
    $this->assertEquals($results, $entity->getResults());

    $this->assertEmpty($entity->getActiveItems());
    $this->assertFalse($entity->isActiveValue('llama'));

    $entity->setActiveItem('llama');
    $this->assertEquals(['llama'], $entity->getActiveItems());
    $this->assertTrue($entity->isActiveValue('llama'));
    $this->assertFalse($entity->isActiveValue('owl'));

    $this->assertFalse($entity->getResults()[0]->isActive());
    $entity->setResults($results);
    $this->assertTrue($entity->getResults()[0]->isActive());

    $this->assertTrue($entity->isActiveValue('llama'));
    $this->assertFalse($entity->isActiveValue('badger'));
    $this->assertFalse($entity->isActiveValue('owl'));

    $entity->setActiveItems(['badger', 'owl']);
    $this->assertFalse($entity->isActiveValue('llama'));
    $this->assertTrue($entity->isActiveValue('badger'));
    $this->assertTrue($entity->isActiveValue('owl'));
  }

  /**
   * Tests field identifier.
   *
   * @covers ::getFieldIdentifier
   * @covers ::setFieldIdentifier
   * @covers ::getFieldAlias
   */
  public function testFieldIdentifier() {
    $entity = new Facet([], 'facets_facet');

    $this->assertEmpty($entity->getFieldIdentifier());

    $entity->setFieldIdentifier('field_owl');
    $this->assertEquals('field_owl', $entity->getFieldIdentifier());
    $this->assertEquals('field_owl', $entity->getFieldAlias());
  }

  /**
   * Tests empty behavior.
   *
   * @covers ::setEmptyBehavior
   * @covers ::getEmptyBehavior
   */
  public function testEmptyBehavior() {
    $entity = new Facet([], 'facets_facet');

    $this->assertEmpty($entity->getEmptyBehavior());

    $entity->setEmptyBehavior(['behavior' => 'none']);
    $this->assertEquals(['behavior' => 'none'], $entity->getEmptyBehavior());
  }

  /**
   * Tests hard limit.
   *
   * @covers ::setHardLimit
   * @covers ::getHardLimit
   */
  public function testHardLimit() {
    $entity = new Facet([], 'facets_facet');
    $this->assertEquals(0, $entity->getHardLimit());
    $entity->setHardLimit(50);
    $this->assertEquals(50, $entity->getHardLimit());
  }

  /**
   * Tests minimum count.
   *
   * @covers ::setMinCount
   * @covers ::getMinCount
   */
  public function testMinCount() {
    $entity = new Facet([], 'facets_facet');
    $this->assertEquals(1, $entity->getMinCount());
    $entity->setMinCount(50);
    $this->assertEquals(50, $entity->getMinCount());
  }

  /**
   * Tests hierarchy settings.
   *
   * @covers ::getHierarchy
   * @covers ::setUseHierarchy
   * @covers ::getUseHierarchy
   * @covers ::setExpandHierarchy
   * @covers ::getExpandHierarchy
   * @covers ::setEnableParentWhenChildGetsDisabled
   * @covers ::getEnableParentWhenChildGetsDisabled
   * @covers ::getHierarchyManager
   * @covers ::getHierarchyInstance
   */
  public function testHierarchySettings() {
    $entity = Facet::create();

    $entity->setUseHierarchy(FALSE);
    $this->assertFalse($entity->getUseHierarchy());
    $entity->setUseHierarchy(TRUE);
    $this->assertTrue($entity->getUseHierarchy());

    $entity->setExpandHierarchy(FALSE);
    $this->assertFalse($entity->getExpandHierarchy());
    $entity->setExpandHierarchy(TRUE);
    $this->assertTrue($entity->getExpandHierarchy());

    $entity->setEnableParentWhenChildGetsDisabled(FALSE);
    $this->assertFalse($entity->getEnableParentWhenChildGetsDisabled());
    $entity->setEnableParentWhenChildGetsDisabled(TRUE);
    $this->assertTrue($entity->getEnableParentWhenChildGetsDisabled());

    $manager = $entity->getHierarchyManager();
    $this->assertInstanceOf(HierarchyPluginManager::class, $manager);
    $this->assertInstanceOf(Taxonomy::class, $entity->getHierarchyInstance());

    $this->assertEquals(['type' => 'taxonomy', 'config' => []], $entity->getHierarchy());
  }

  /**
   * Tests that the block caches are cleared from API calls.
   *
   * @covers ::postSave
   * @covers ::postDelete
   * @covers ::clearBlockCache
   */
  public function testBlockCache() {
    // Block processing requires the system module.
    $this->enableModules(['system']);

    // Create our facet.
    $entity = Facet::create([
      'id' => 'test_facet',
      'name' => 'Test facet',
    ]);
    $entity->setWidget('links');
    $entity->setEmptyBehavior(['behavior' => 'none']);

    $block_id = 'facet_block' . PluginBase::DERIVATIVE_SEPARATOR . $entity->id();

    // Check we don't have a block yet.
    $this->assertFalse($this->container->get('plugin.manager.block')->hasDefinition($block_id));

    // Save our facet.
    $entity->save();

    // Check our block exists.
    $this->assertTrue($this->container->get('plugin.manager.block')->hasDefinition($block_id));

    // Delete our facet.
    $entity->delete();

    // Check our block exists.
    $this->assertFalse($this->container->get('plugin.manager.block')->hasDefinition($block_id));
  }

}
