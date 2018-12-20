<?php

namespace Drupal\Tests\facets_summary\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Class ProcessorTest.
 *
 * @group facets
 */
class ProcessorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'facets',
    'facets_summary',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('facets_facet');
    $this->installEntitySchema('facets_summary');
  }

  /**
   * Tests that the "hide when not rendered" processors is last.
   */
  public function testHideWhenNotRenderedIsLast() {
    /** @var \Drupal\facets_summary\Processor\ProcessorPluginManager $processor_manager */
    $processor_manager = $this->container->get('plugin.manager.facets_summary.processor');
    $defs = $processor_manager->getDefinitions();
    $hide_when_not_rendered_weight = $defs['hide_when_not_rendered']['stages']['build'];
    unset($defs['hide_when_not_rendered']);
    foreach ($defs as $def) {
      $this->assertLessThan($hide_when_not_rendered_weight, $def['stages']['build']);
    }
  }

}
